<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ArticleImportController extends Controller
{
    public function import(Request $request)
    {
        $data = $request->validate([
            'source_url' => [
                'required',
                'url',
                'max:2048',
            ],
        ]);

        try {
            $result = $this->fetchArticle(
                trim((string) $data['source_url'])
            );

            return response()->json($result);

        } catch (Throwable $e) {
            logger()->warning(
                'Article URL import failed',
                [
                    'url' => $data['source_url'],
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    Str::limit(
                        $e->getMessage(),
                        260,
                        ''
                    ),
            ], 422);
        }
    }

    private function fetchArticle(
        string $url
    ): array {
        $currentUrl = $url;
        $response = null;

        /*
         * Follow at most 3 redirects manually so every destination
         * can be checked before the server connects to it.
         */
        for ($redirect = 0; $redirect <= 3; $redirect++) {
            $this->assertPublicUrl($currentUrl);

            $response = Http::withHeaders([
                    'User-Agent' =>
                        'Mozilla/5.0 (compatible; BackThenStoriesImporter/1.0)',
                    'Accept' =>
                        'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' =>
                        'en-US,en;q=0.9',
                ])
                ->withOptions([
                    'allow_redirects' => false,
                ])
                ->connectTimeout(3)
                ->timeout(10)
                ->get($currentUrl);

            if (
                $response->status() >= 300 &&
                $response->status() < 400
            ) {
                $location =
                    $response->header('Location');

                if (!$location) {
                    throw new RuntimeException(
                        'The source returned a redirect without a destination.'
                    );
                }

                $currentUrl =
                    $this->resolveUrl(
                        $currentUrl,
                        $location
                    );

                continue;
            }

            break;
        }

        if (!$response || !$response->successful()) {
            throw new RuntimeException(
                'Could not load the public source page. HTTP '
                . ($response?->status() ?? 'error')
                . '.'
            );
        }

        $contentType = mb_strtolower(
            (string) $response->header(
                'Content-Type'
            )
        );

        if (
            $contentType !== '' &&
            !str_contains(
                $contentType,
                'text/html'
            ) &&
            !str_contains(
                $contentType,
                'application/xhtml'
            )
        ) {
            throw new RuntimeException(
                'The URL did not return an HTML article page.'
            );
        }

        $html = (string) $response->body();

        if (strlen($html) > 4 * 1024 * 1024) {
            throw new RuntimeException(
                'The source page is too large to import safely.'
            );
        }

        if (!class_exists(DOMDocument::class)) {
            throw new RuntimeException(
                'The server DOM extension is unavailable.'
            );
        }

        $dom = new DOMDocument();

        $previous =
            libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_NOWARNING | LIBXML_NOERROR
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new RuntimeException(
                'Could not parse the source page.'
            );
        }

        $xpath = new DOMXPath($dom);

        $this->removeNoise($xpath);

        $title =
            $this->firstMeta(
                $xpath,
                [
                    ['property', 'og:title'],
                    ['name', 'twitter:title'],
                ]
            )
            ?: $this->firstText(
                $xpath,
                [
                    '//h1[1]',
                    '//title[1]',
                ]
            );

        $title = trim(
            html_entity_decode(
                strip_tags((string) $title),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );

        $featuredImage =
            $this->firstMeta(
                $xpath,
                [
                    ['property', 'og:image'],
                    ['name', 'twitter:image'],
                    ['name', 'twitter:image:src'],
                ]
            );

        if ($featuredImage) {
            $featuredImage =
                $this->resolveUrl(
                    $currentUrl,
                    $featuredImage
                );

            try {
                $this->assertPublicUrl(
                    $featuredImage
                );
            } catch (Throwable $e) {
                $featuredImage = null;
            }
        }

        $root =
            $this->findBestContentNode(
                $xpath
            );

        if (!$root) {
            throw new RuntimeException(
                'Could not locate the main article content on this page.'
            );
        }

        $body =
            $this->cleanArticleHtml(
                $root
            );

        $plainText = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                strip_tags($body)
            )
        );

        if (mb_strlen($plainText) < 250) {
            throw new RuntimeException(
                'The importer found too little article text. The page may block automated access or use an unsupported layout.'
            );
        }

        if ($title === '') {
            $title =
                Str::limit(
                    $plainText,
                    90,
                    ''
                );
        }

        return [
            'title' => Str::limit(
                $title,
                255,
                ''
            ),
            'body' => $body,
            'featured_image_url' =>
                $featuredImage,
            'source_url' => $currentUrl,
            'source_host' =>
                parse_url(
                    $currentUrl,
                    PHP_URL_HOST
                ),
        ];
    }

    private function findBestContentNode(
        DOMXPath $xpath
    ): ?DOMNode {
        foreach (
            [
                '//article[1]',
                '//main//article[1]',
                '//main[1]',
            ] as $query
        ) {
            $nodes = $xpath->query($query);

            if (
                $nodes &&
                $nodes->length > 0 &&
                $this->textLength(
                    $nodes->item(0)
                ) >= 250
            ) {
                return $nodes->item(0);
            }
        }

        $candidates =
            $xpath->query(
                '//div | //section'
            );

        $best = null;
        $bestScore = 0;

        if (!$candidates) {
            return null;
        }

        foreach ($candidates as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $identity = mb_strtolower(
                $node->getAttribute('id')
                . ' '
                . $node->getAttribute('class')
            );

            if (
                preg_match(
                    '/nav|menu|sidebar|footer|header|comment|related|advert|ads-|cookie|share|social|newsletter|popup|modal|recommend/',
                    $identity
                )
            ) {
                continue;
            }

            $textLength =
                $this->textLength($node);

            if ($textLength < 250) {
                continue;
            }

            $paragraphs =
                $node->getElementsByTagName('p')
                    ->length;

            $headings =
                $node->getElementsByTagName('h2')
                    ->length
                + $node->getElementsByTagName('h3')
                    ->length;

            $score =
                $textLength
                + ($paragraphs * 120)
                + ($headings * 40);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $node;
            }
        }

        return $best;
    }

    private function cleanArticleHtml(
        DOMNode $root
    ): string {
        $doc =
            $root->ownerDocument;

        $xpath =
            new DOMXPath($doc);

        $nodes =
            $xpath->query(
                './/p | .//h2 | .//h3 | .//blockquote | .//ul | .//ol',
                $root
            );

        if (!$nodes) {
            return '';
        }

        $parts = [];

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tag =
                mb_strtolower(
                    $node->tagName
                );

            /*
             * UL/OL already contains LI text. Do not also emit
             * paragraph descendants from inside the same list.
             */
            if (
                in_array(
                    $tag,
                    ['p', 'h2', 'h3', 'blockquote'],
                    true
                ) &&
                $this->hasAncestorTag(
                    $node,
                    ['ul', 'ol']
                )
            ) {
                continue;
            }

            if (
                in_array(
                    $tag,
                    ['ul', 'ol'],
                    true
                )
            ) {
                $items = [];

                foreach (
                    $node->childNodes
                    as $child
                ) {
                    if (
                        $child instanceof DOMElement &&
                        mb_strtolower(
                            $child->tagName
                        ) === 'li'
                    ) {
                        $text =
                            $this->cleanText(
                                $child->textContent
                            );

                        if (
                            mb_strlen($text) >= 2
                        ) {
                            $items[] =
                                '<li>'
                                . e($text)
                                . '</li>';
                        }
                    }
                }

                if ($items) {
                    $parts[] =
                        '<' . $tag . '>'
                        . implode('', $items)
                        . '</' . $tag . '>';
                }

                continue;
            }

            $text =
                $this->cleanText(
                    $node->textContent
                );

            $minimum =
                $tag === 'p'
                    ? 20
                    : 3;

            if (
                mb_strlen($text)
                < $minimum
            ) {
                continue;
            }

            $parts[] =
                '<' . $tag . '>'
                . e($text)
                . '</' . $tag . '>';
        }

        return implode(
            "\n",
            $parts
        );
    }

    private function removeNoise(
        DOMXPath $xpath
    ): void {
        $queries = [
            '//script',
            '//style',
            '//noscript',
            '//svg',
            '//form',
            '//button',
            '//nav',
            '//footer',
            '//header',
            '//aside',
            '//iframe',
        ];

        foreach ($queries as $query) {
            $nodes =
                $xpath->query($query);

            if (!$nodes) {
                continue;
            }

            $remove = [];

            foreach ($nodes as $node) {
                $remove[] = $node;
            }

            foreach ($remove as $node) {
                $node->parentNode?->removeChild(
                    $node
                );
            }
        }
    }

    private function firstMeta(
        DOMXPath $xpath,
        array $lookups
    ): ?string {
        foreach ($lookups as [$attribute, $value]) {
            $query = sprintf(
                '//meta[translate(@%s,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="%s"]/@content',
                $attribute,
                mb_strtolower($value)
            );

            $nodes =
                $xpath->query($query);

            if (
                $nodes &&
                $nodes->length > 0
            ) {
                $content =
                    trim(
                        (string) $nodes->item(0)
                            ?->nodeValue
                    );

                if ($content !== '') {
                    return $content;
                }
            }
        }

        return null;
    }

    private function firstText(
        DOMXPath $xpath,
        array $queries
    ): ?string {
        foreach ($queries as $query) {
            $nodes =
                $xpath->query($query);

            if (
                $nodes &&
                $nodes->length > 0
            ) {
                $text =
                    $this->cleanText(
                        $nodes->item(0)
                            ?->textContent
                    );

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    private function hasAncestorTag(
        DOMNode $node,
        array $tags
    ): bool {
        $parent = $node->parentNode;

        while ($parent) {
            if (
                $parent instanceof DOMElement &&
                in_array(
                    mb_strtolower(
                        $parent->tagName
                    ),
                    $tags,
                    true
                )
            ) {
                return true;
            }

            $parent =
                $parent->parentNode;
        }

        return false;
    }

    private function textLength(
        DOMNode $node
    ): int {
        return mb_strlen(
            $this->cleanText(
                $node->textContent
            )
        );
    }

    private function cleanText(
        ?string $text
    ): string {
        $text =
            html_entity_decode(
                (string) $text,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

        $text =
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            );

        return trim(
            is_string($text)
                ? $text
                : ''
        );
    }

    private function assertPublicUrl(
        string $url
    ): void {
        $parts =
            parse_url($url);

        if (
            !is_array($parts) ||
            !isset(
                $parts['scheme'],
                $parts['host']
            ) ||
            !in_array(
                mb_strtolower(
                    $parts['scheme']
                ),
                ['http', 'https'],
                true
            )
        ) {
            throw new RuntimeException(
                'Only normal public http/https URLs can be imported.'
            );
        }

        $host =
            mb_strtolower(
                (string) $parts['host']
            );

        if (
            $host === 'localhost' ||
            str_ends_with(
                $host,
                '.localhost'
            )
        ) {
            throw new RuntimeException(
                'Local/private URLs cannot be imported.'
            );
        }

        $ips =
            gethostbynamel($host)
            ?: [];

        /*
         * If the host itself is an IP address, validate it directly.
         */
        if (
            filter_var(
                $host,
                FILTER_VALIDATE_IP
            )
        ) {
            $ips[] = $host;
        }

        if (!$ips) {
            throw new RuntimeException(
                'The source hostname could not be resolved.'
            );
        }

        foreach (
            array_unique($ips)
            as $ip
        ) {
            $public =
                filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE
                    | FILTER_FLAG_NO_RES_RANGE
                );

            if (!$public) {
                throw new RuntimeException(
                    'Local/private network URLs cannot be imported.'
                );
            }
        }
    }

    private function resolveUrl(
        string $base,
        string $target
    ): string {
        $target = trim($target);

        if (
            preg_match(
                '#^https?://#i',
                $target
            )
        ) {
            return $target;
        }

        $baseParts =
            parse_url($base);

        $scheme =
            $baseParts['scheme']
            ?? 'https';

        $host =
            $baseParts['host']
            ?? '';

        $port =
            isset($baseParts['port'])
                ? ':' . $baseParts['port']
                : '';

        if (
            str_starts_with(
                $target,
                '//'
            )
        ) {
            return $scheme
                . ':'
                . $target;
        }

        if (
            str_starts_with(
                $target,
                '/'
            )
        ) {
            return $scheme
                . '://'
                . $host
                . $port
                . $target;
        }

        $path =
            $baseParts['path']
            ?? '/';

        $directory =
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    dirname($path)
                ),
                '/'
            );

        return $scheme
            . '://'
            . $host
            . $port
            . ($directory ? $directory : '')
            . '/'
            . ltrim($target, '/');
    }
}
