(() => {
    'use strict';

    const form = document.getElementById('articleForm');

    if (!form || form.dataset.adsenseOneClickV363 === '1') {
        return;
    }

    form.dataset.adsenseOneClickV363 = '1';

    const editor = document.getElementById('editor');
    const titleField = form.querySelector('[name="title"]');
    const excerptField = form.querySelector('[name="excerpt"]');
    const seoTitleField = form.querySelector('[name="seo_title"]');
    const metaField = form.querySelector('[name="meta_description"]');
    const categoryField = form.querySelector('[name="category_id"]');
    const statusSelect = form.querySelector('select[name="status"]');
    const slugField = form.querySelector('[name="slug"]');

    const aiUrl = '/admin/articles/adsense-review';

    const PENDING_KEY = 'adsenseV31PendingFullScan';
    const RESULT_KEY = 'adsenseV31LastResult';

    let lastFingerprint = '';
    let finalResult = '';
    let manualReviewRequired = false;

    function injectStyles() {
        if (document.getElementById('adsenseV31Styles')) return;

        const style = document.createElement('style');
        style.id = 'adsenseV31Styles';

        style.textContent = `
            .as31-box{
                margin:18px 0;
                padding:15px;
                border:1px solid #dbe3ec;
                border-radius:12px;
                background:#fff;
            }
            .as31-head{
                display:flex;
                align-items:flex-start;
                justify-content:space-between;
                gap:10px;
            }
            .as31-title{
                font-size:15px;
                font-weight:850;
            }
            .as31-sub{
                margin-top:4px;
                color:#64748b;
                font-size:10px;
                line-height:1.45;
            }
            .as31-btn{
                width:100%;
                margin-top:12px;
                min-height:44px;
                border:0;
                border-radius:9px;
                background:#171717;
                color:#fff;
                font-size:12px;
                font-weight:850;
                cursor:pointer;
            }
            .as31-btn:disabled{
                opacity:.65;
                cursor:wait;
            }
            .as31-status{
                display:inline-flex;
                align-items:center;
                padding:5px 9px;
                border-radius:999px;
                background:#eef2f7;
                color:#475569;
                font-size:10px;
                font-weight:850;
                white-space:nowrap;
            }
            .as31-status.pass{
                background:#e9f8ef;
                color:#166534;
            }
            .as31-status.review{
                background:#fff7d6;
                color:#854d0e;
            }
            .as31-status.risk{
                background:#feecec;
                color:#991b1b;
            }
            .as31-progress{
                display:none;
                margin-top:12px;
                padding:10px;
                border:1px solid #e5e7eb;
                border-radius:9px;
                background:#fafafa;
            }
            .as31-progress.open{
                display:block;
            }
            .as31-step{
                display:grid;
                grid-template-columns:18px 1fr;
                gap:7px;
                margin:6px 0;
                font-size:11px;
                line-height:1.4;
                color:#475569;
            }
            .as31-step.pass{color:#166534}
            .as31-step.warn{color:#854d0e}
            .as31-step.block{color:#991b1b}
            .as31-section{
                margin-top:12px;
            }
            .as31-section-title{
                margin-bottom:6px;
                font-size:10px;
                font-weight:850;
                letter-spacing:.06em;
                text-transform:uppercase;
                color:#64748b;
            }
            .as31-card{
                margin-top:7px;
                padding:9px 10px;
                border:1px solid #e5e7eb;
                border-radius:8px;
                background:#fafafa;
                font-size:11px;
                line-height:1.45;
            }
            .as31-card strong{
                display:block;
                margin-bottom:3px;
            }
            .as31-list{
                margin:5px 0 0 16px;
                padding:0;
            }
            .as31-list li{
                margin:3px 0;
            }
            .as31-manual{
                display:none;
                margin-top:12px;
                padding-top:10px;
                border-top:1px solid #eee;
            }
            .as31-manual.open{
                display:block;
            }
            .as31-manual label{
                display:flex;
                gap:7px;
                align-items:flex-start;
                font-size:11px;
                line-height:1.45;
            }
            .as31-manual input{
                width:auto;
                margin-top:2px;
            }
            .as31-note{
                margin-top:10px;
                color:#64748b;
                font-size:10px;
                line-height:1.45;
            }
        `;

        document.head.appendChild(style);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    function normalizeUrl(value) {
        try {
            const url = new URL(String(value || ''), window.location.origin);
            url.hash = '';
            url.search = '';
            if (url.pathname.length > 1) {
                url.pathname = url.pathname.replace(/\/+$/, '');
            }
            return url.href;
        } catch (e) {
            return String(value || '').trim();
        }
    }

    function expectedCanonicalUrl() {
        const slug = (slugField?.value || '').trim();

        if (!slug) {
            return '';
        }

        return normalizeUrl(
            window.location.origin
            + '/story/'
            + encodeURIComponent(slug)
        );
    }

    function describeAd(ad, index) {
        if (!ad) {
            return 'Ad slot #' + (index + 1);
        }

        const explicit =
            ad.getAttribute('data-ad-slot')
            || ad.getAttribute('data-ad-unit')
            || ad.getAttribute('data-gam-slot')
            || ad.id
            || '';

        if (explicit) {
            return explicit;
        }

        const classes =
            String(ad.className || '')
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .filter(name =>
                    /ad|banner|policy/i.test(name)
                )
                .slice(0, 3)
                .join('.');

        return classes
            ? '.' + classes
            : 'Ad slot #' + (index + 1);
    }

    function listHtml(items) {
        if (!Array.isArray(items) || !items.length) {
            return '<div class="as31-sub">Không ghi nhận.</div>';
        }

        return '<ul class="as31-list">' +
            items.map(item => '<li>' + escapeHtml(item) + '</li>').join('') +
            '</ul>';
    }

    function getBodyHtml() {
        if (typeof window.syncEditor === 'function') {
            window.syncEditor();
        } else if (typeof syncEditor === 'function') {
            syncEditor();
        }

        return editor?.innerHTML || form.querySelector('[name="body"]')?.value || '';
    }

    function syncAllEditors() {
        try {
            if (typeof syncEditor === 'function') {
                syncEditor();
            }
        } catch (e) {}

        try {
            if (typeof syncChaptersJson === 'function') {
                syncChaptersJson();
            }
        } catch (e) {}
    }

    function textFromHtml(html) {
        const div = document.createElement('div');
        div.innerHTML = html || '';

        return (div.innerText || div.textContent || '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function fingerprint() {
        const featuredFile =
            form.querySelector('[name="featured_image_file"]')
            ?.files?.[0]?.name || '';

        const value = [
            titleField?.value || '',
            excerptField?.value || '',
            getBodyHtml(),
            seoTitleField?.value || '',
            metaField?.value || '',
            categoryField?.value || '',
            form.querySelector('[name="content_mode"]:checked')?.value || '',
            form.querySelector('[name="featured_media_id"]')?.value || '',
            form.querySelector('[name="imported_featured_image_url"]')?.value || '',
            featuredFile
        ].join('\n');

        let hash = 2166136261;

        for (let i = 0; i < value.length; i++) {
            hash ^= value.charCodeAt(i);
            hash = Math.imul(hash, 16777619);
        }

        return String(hash >>> 0);
    }

    function getPreviewLink() {
        return [...document.querySelectorAll('a[target="_blank"]')]
            .find(a => /preview|xem trước/i.test(a.textContent || ''));
    }

    function getLocalStats() {
        const html = getBodyHtml();

        const container = document.createElement('div');
        container.innerHTML = html;

        const bodyText = textFromHtml(html);
        const words = bodyText
            ? bodyText.split(/\s+/).filter(Boolean).length
            : 0;

        const youtube = [...container.querySelectorAll('iframe')]
            .filter(x =>
                /youtube\.com\/embed|youtube-nocookie\.com\/embed/i
                    .test(x.getAttribute('src') || '')
            ).length;

        const images = container.querySelectorAll('img').length;

        return {
            words,
            youtube,
            images,
            bodyText,
            html
        };
    }

    function localScan() {
        const stats = getLocalStats();

        const blocks = [];
        const warnings = [];

        const title = (titleField?.value || '').trim();
        const excerpt = (excerptField?.value || '').trim();

        if (!title) {
            blocks.push('Article title is missing.');
        }

        if (stats.words < 120) {
            blocks.push('Article body is too incomplete for policy review.');
        } else if (stats.words < 300) {
            warnings.push('Article has relatively little publisher text; review whether it provides substantial value.');
        }

        const combined = [
            title,
            excerpt,
            stats.bodyText,
            seoTitleField?.value || '',
            metaField?.value || ''
        ].join(' ').toLowerCase();

        const placeholders = [
            'lorem ipsum',
            'replace this',
            'coming soon',
            'under construction',
            'your publisher cms is ready',
            '[placeholder]'
        ].filter(x => combined.includes(x));

        if (placeholders.length) {
            blocks.push('Unfinished placeholder text detected: ' + placeholders.join(', '));
        }

        if (!categoryField?.value) {
            warnings.push('No category selected.');
        }

        if (stats.youtube > 0 && stats.words < 300) {
            warnings.push('YouTube/embed may be too dominant compared with publisher text.');
        }

        return {
            ...stats,
            blocks,
            warnings
        };
    }

    function step(level, title, detail) {
        const icon =
            level === 'pass' ? '✓' :
            level === 'block' ? '✕' :
            level === 'warn' ? '!' :
            '•';

        return `
            <div class="as31-step ${level}">
                <div>${icon}</div>
                <div>
                    <strong>${escapeHtml(title)}</strong><br>
                    ${escapeHtml(detail)}
                </div>
            </div>
        `;
    }

    injectStyles();

    // Remove old V3 panel if present. Keep V2/local panel.
    document.querySelectorAll('.arv3-box').forEach(el => el.remove());

    const sideCard =
        statusSelect?.closest('.card') ||
        form.querySelector('aside .card') ||
        form;

    const saveButton = [...sideCard.querySelectorAll('button')]
        .find(btn => /save article|lưu bài viết/i.test(btn.textContent || ''));

    const panel = document.createElement('div');
    panel.className = 'as31-box';

    panel.innerHTML = `
        <div class="as31-head">
            <div>
                <div class="as31-title">Kiểm tra an toàn AdSense một chạm V3.6.3</div>
                <div class="as31-sub">
                    Quét nội dung → tự lưu Draft an toàn → Gemini đánh giá chính sách → kiểm tra Preview/Live → tổng hợp kết quả.
                </div>
            </div>
            <span id="as31Status" class="as31-status">CHƯA CHẠY</span>
        </div>

        <button type="button" id="as31Run" class="as31-btn">
            ✨ CHẠY KIỂM TRA ADSENSE TOÀN BỘ
        </button>

        <div id="as31Progress" class="as31-progress"></div>

        <div id="as31AiSection" class="as31-section" style="display:none">
            <div class="as31-section-title">Đánh giá chính sách bằng Gemini AI</div>
            <div id="as31AiResult"></div>
        </div>

        <div id="as31LiveSection" class="as31-section" style="display:none">
            <div class="as31-section-title">Kiểm tra trang Preview / Live</div>
            <div id="as31LiveResult"></div>
        </div>

        <div id="as31Manual" class="as31-manual">
            <label>
                <input type="checkbox" id="as31ManualBox">
                <span>
                    Tôi đã tự kiểm tra các cảnh báo và xác nhận bài có giá trị biên tập riêng,
                    quyền sử dụng media đã được xem xét và không cố tình bỏ qua vấn đề chính sách nào đã biết.
                </span>
            </label>
        </div>

        <div class="as31-note">
            Đây là công cụ kiểm tra an toàn biên tập theo hướng thận trọng, không phải chứng nhận duyệt của Google.
            Công cụ không thể chứng minh quyền sở hữu bản quyền, giấy phép, đạo văn hoặc đảm bảo AdSense sẽ duyệt.
        </div>
    `;

    if (saveButton) {
        sideCard.insertBefore(panel, saveButton);
    } else {
        sideCard.appendChild(panel);
    }

    const runBtn = document.getElementById('as31Run');
    const statusBadge = document.getElementById('as31Status');
    const progress = document.getElementById('as31Progress');
    const aiSection = document.getElementById('as31AiSection');
    const aiResult = document.getElementById('as31AiResult');
    const liveSection = document.getElementById('as31LiveSection');
    const liveResult = document.getElementById('as31LiveResult');
    const manualWrap = document.getElementById('as31Manual');
    const manualBox = document.getElementById('as31ManualBox');

    function setStatus(text, cls = '') {
        statusBadge.textContent = text;
        statusBadge.className = 'as31-status' + (cls ? ' ' + cls : '');
    }

    function addProgress(level, title, detail, key = '') {
        progress.classList.add('open');

        const html = step(level, title, detail);

        if (!key) {
            progress.insertAdjacentHTML('beforeend', html);
            return;
        }

        let row = progress.querySelector(
            '[data-progress-key="' + CSS.escape(key) + '"]'
        );

        if (!row) {
            const wrap = document.createElement('div');
            wrap.dataset.progressKey = key;
            wrap.innerHTML = html;
            progress.appendChild(wrap);
            return;
        }

        row.innerHTML = html;
    }

    function updateProgress(key, level, title, detail) {
        addProgress(level, title, detail, key);
    }

    function resetUi() {
        progress.innerHTML = '';
        progress.classList.add('open');
        aiResult.innerHTML = '';
        liveResult.innerHTML = '';
        aiSection.style.display = 'none';
        liveSection.style.display = 'none';
        manualWrap.classList.remove('open');
        manualBox.checked = false;
        manualReviewRequired = false;
    }

    function clickExistingLocalChecker() {
        const candidates = [...document.querySelectorAll('button[type="button"]')];

        const button = candidates.find(btn =>
            /run check|run policy check/i.test(btn.textContent || '')
        );

        if (button) {
            try {
                button.click();
            } catch (e) {}
        }
    }

    async function safeAutoSaveDraft() {
        syncAllEditors();

        const currentStatus = statusSelect?.value || 'draft';

        if (currentStatus === 'published') {
            throw new Error(
                'This article is already Published. To avoid publishing unreviewed edits, One-Click Check will not auto-save over a live article. Use a Draft/Review copy for major edits.'
            );
        }

        const data = new FormData(form);

        data.set('status', 'draft');
        data.set('adsense_auto_draft', '1');

        // Never let an automatic safety scan accidentally publish.
        if (statusSelect) {
            statusSelect.value = 'draft';
        }

        const response = await fetch(form.action, {
            method: 'POST',
            body: data,
            headers: {
                'Accept': 'text/html'
            },
            credentials: 'same-origin',
            redirect: 'follow'
        });

        if (!response.ok) {
            throw new Error(
                'Automatic Draft save failed (HTTP ' + response.status + ').'
            );
        }

        const finalUrl = response.url || '';

        const newArticleSaved =
            /\/admin\/articles\/\d+\/edit(?:$|\?)/i.test(finalUrl);

        const alreadyExisting =
            /\/admin\/articles\/\d+\/edit(?:$|\?)/i.test(window.location.href);

        if (!alreadyExisting && !newArticleSaved) {
            throw new Error(
                'Automatic Draft save did not reach an Edit page. Check required fields and validation errors.'
            );
        }

        return {
            finalUrl: newArticleSaved ? finalUrl : window.location.href,
            needsReload: !alreadyExisting
        };
    }

    async function runAiReview(local) {
        const response = await fetch(aiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.content ||
                    form.querySelector('input[name="_token"]')?.value ||
                    ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                title: (titleField?.value || '').trim(),
                body: local.html,
                source_text: '',
                youtube_count: local.youtube,
                image_count: local.images
            })
        });

        let data = {};

        try {
            data = await response.json();
        } catch (e) {
            data = {};
        }

        if (!response.ok) {
            throw new Error(
                data.message || 'Gemini AI AdSense Review failed.'
            );
        }

        return data;
    }

    function renderAi(data) {
        aiSection.style.display = '';

        aiResult.innerHTML = `
            <div class="as31-card">
                <strong>Kết quả tổng thể</strong>
                ${escapeHtml(data.overall || 'NEED_REVIEW')}
            </div>
            <div class="as31-card">
                <strong>Giá trị biên tập riêng</strong>
                ${escapeHtml(data.original_value || '')}
            </div>
            <div class="as31-card">
                <strong>Rủi ro nội dung sao chép / tái tạo</strong>
                ${escapeHtml(data.replicated_content_risk || '')}
            </div>
            <div class="as31-card">
                <strong>So sánh tính nguyên bản</strong>
                ${escapeHtml(data.source_comparison || '')}
            </div>
            <div class="as31-card">
                <strong>YouTube / nội dung nhúng</strong>
                ${escapeHtml(data.youtube_embed_assessment || '')}
            </div>
            <div class="as31-card">
                <strong>Các mục cần kiểm chứng</strong>
                ${listHtml(data.fact_check_items)}
            </div>
            <div class="as31-card">
                <strong>Các mục về quyền media</strong>
                ${listHtml(data.media_rights_items)}
            </div>
            <div class="as31-card">
                <strong>Cảnh báo chính sách</strong>
                ${listHtml(data.policy_flags)}
            </div>
            <div class="as31-card">
                <strong>Các mục cần xử lý</strong>
                ${listHtml(data.required_fixes)}
            </div>
        `;
    }

    function checkImage(url) {
        return new Promise(resolve => {
            const img = new Image();
            let done = false;

            const finish = ok => {
                if (done) return;
                done = true;
                resolve({ url, ok });
            };

            img.onload = () => finish(true);
            img.onerror = () => finish(false);

            setTimeout(() => finish(false), 8000);

            img.src = url;
        });
    }

    async function runLiveAudit() {
        const previewLink = getPreviewLink();

        if (!previewLink?.href) {
            throw new Error(
                'Preview URL is not available yet.'
            );
        }

        const rows = [];
        let blocks = 0;
        let warnings = 0;

        const add = (level, title, detail) => {
            rows.push(step(level, title, detail));

            if (level === 'block') blocks++;
            if (level === 'warn') warnings++;
        };

        const response = await fetch(previewLink.href, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'text/html'
            }
        });

        if (!response.ok) {
            add(
                'block',
                'Preview HTTP',
                'Preview returned HTTP ' + response.status + '.'
            );

            return { rows, blocks: blocks + 1, warnings };
        }

        add('pass', 'Preview HTTP', 'HTTP ' + response.status + '.');

        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        const pageTitle = (doc.querySelector('title')?.textContent || '').trim();

        if (pageTitle) {
            add('pass', 'Page title', pageTitle);
        } else {
            add('block', 'Page title', 'No <title> rendered.');
        }

        const canonical =
            doc.querySelector('link[rel="canonical"]')?.getAttribute('href') || '';

        const normalizedCanonical =
            normalizeUrl(canonical);

        const expectedCanonical =
            expectedCanonicalUrl();

        if (!/^https:\/\//i.test(canonical)) {
            add(
                'block',
                'Canonical',
                'Missing or invalid HTTPS canonical URL.'
            );

        } else if (
            expectedCanonical
            && normalizedCanonical !== expectedCanonical
        ) {
            add(
                'block',
                'Canonical',
                'Canonical đang trỏ sai bài. Expected: '
                    + expectedCanonical
                    + ' | Found: '
                    + normalizedCanonical
            );

        } else if (!expectedCanonical) {
            add(
                'warn',
                'Canonical',
                'Có canonical hợp lệ nhưng không đọc được slug hiện tại để đối chiếu chính xác: '
                    + normalizedCanonical
            );

        } else {
            add(
                'pass',
                'Canonical',
                'Khớp đúng bài hiện tại: ' + normalizedCanonical
            );
        }

        const main =
            doc.querySelector('.article .body') ||
            doc.querySelector('.body') ||
            doc.querySelector('article') ||
            doc.querySelector('main');

        const renderedText =
            (main?.textContent || '')
                .replace(/\s+/g, ' ')
                .trim();

        const renderedWords =
            renderedText
                ? renderedText.split(/\s+/).filter(Boolean).length
                : 0;

        if (renderedWords < 120) {
            add(
                'block',
                'Rendered publisher content',
                renderedWords + ' rendered words.'
            );
        } else {
            add(
                'pass',
                'Rendered publisher content',
                renderedWords + ' rendered words.'
            );
        }

        const lower = html.toLowerCase();

        const placeholders = [
            'lorem ipsum',
            'replace this',
            'coming soon',
            'under construction',
            'your publisher cms is ready',
            '[placeholder]'
        ].filter(x => lower.includes(x));

        if (placeholders.length) {
            add(
                'block',
                'Placeholder content',
                'Found: ' + placeholders.join(', ')
            );
        } else {
            add(
                'pass',
                'Placeholder content',
                'No common unfinished-page text detected.'
            );
        }

        const policyPaths = [
            ['/about', 'About'],
            ['/contact', 'Contact'],
            ['/privacy', 'Privacy'],
            ['/terms', 'Terms'],
            ['/editorial-policy', 'Editorial Policy']
        ];

        for (const [path, label] of policyPaths) {
            const found = [...doc.querySelectorAll('a[href]')]
                .some(a => {
                    try {
                        return new URL(
                            a.getAttribute('href'),
                            previewLink.href
                        ).pathname === path;
                    } catch (e) {
                        return false;
                    }
                });

            add(
                found ? 'pass' : 'warn',
                label + ' link',
                found
                    ? 'Present on rendered page.'
                    : 'No ' + path + ' link found on this rendered page.'
            );
        }

        const iframes = [...doc.querySelectorAll('iframe')];

        const youtube = iframes.filter(x =>
            /youtube\.com\/embed|youtube-nocookie\.com\/embed/i
                .test(x.getAttribute('src') || '')
        );

        const invalidYoutube = youtube.filter(x =>
            !/^https:\/\/(www\.)?youtube(-nocookie)?\.com\/embed\/[A-Za-z0-9_-]+/i
                .test(x.getAttribute('src') || '')
        );

        if (invalidYoutube.length) {
            add(
                'block',
                'YouTube embed',
                invalidYoutube.length + ' invalid YouTube iframe URL(s).'
            );
        } else if (youtube.length) {
            add(
                'pass',
                'YouTube embed',
                youtube.length + ' valid YouTube iframe(s) rendered.'
            );
        } else {
            add(
                'pass',
                'YouTube embed',
                'No YouTube iframe detected; this is fine.'
            );
        }

        const imageUrls = [...doc.querySelectorAll('img[src]')]
            .map(img => {
                try {
                    return new URL(
                        img.getAttribute('src'),
                        previewLink.href
                    ).href;
                } catch (e) {
                    return '';
                }
            })
            .filter(Boolean)
            .slice(0, 30);

        if (imageUrls.length) {
            const checks = await Promise.all(
                imageUrls.map(checkImage)
            );

            const broken = checks.filter(x => !x.ok);

            if (broken.length) {
                add(
                    'block',
                    'Ảnh đã render',
                    broken.length + ' ảnh tải thất bại.'
                );
            } else {
                add(
                    'pass',
                    'Ảnh đã render',
                    imageUrls.length + ' ảnh đã tải thành công.'
                );
            }
        } else {
            add(
                'pass',
                'Ảnh đã render',
                'Không phát hiện ảnh. Bài viết không bắt buộc phải có ảnh.'
            );
        }

        /*
         * GOOGLE ADS / PUBLISHER-CONTENT POLICY CHECK
         *
         * Google does NOT publish a rule such as "3 ads = violation".
         * Therefore the scanner must not warn merely because it finds 3 slots.
         *
         * What we can check structurally:
         * 1) whether ad/promo elements appear to overwhelm publisher-content,
         * 2) whether ads are placed inside / next to interactive controls in a
         *    way that may encourage accidental clicks,
         * 3) whether nearby labels/headings are misleading,
         * 4) whether the page still has meaningful publisher-content.
         *
         * The final served-ad layout can still change after Google fills ads,
         * so this remains an advisory structural audit, not a Google verdict.
         */
        const adSelector =
            '[data-gam-slot], ins.adsbygoogle, .ad, [data-ad-slot], [data-ad-unit]';

        const policyAdZones =
            [...doc.querySelectorAll('.policy-ad-zone')];

        const adElements =
            policyAdZones.length
                ? policyAdZones
                : [...doc.querySelectorAll(adSelector)];

        const ads =
            adElements.length;

        const publisherRoot =
            main
            || doc.querySelector('article')
            || doc.querySelector('main')
            || doc.body;

        const publisherText =
            (publisherRoot?.textContent || '')
                .replace(/\s+/g, ' ')
                .trim();

        const publisherWords =
            publisherText
                ? publisherText
                    .split(/\s+/)
                    .filter(Boolean)
                    .length
                : 0;

        /*
         * This block count is only an internal warning heuristic.
         * It is NOT a Google threshold.
         */
        const publisherBlocks =
            publisherRoot
                ? publisherRoot.querySelectorAll(
                    'p, h1, h2, h3, h4, blockquote, img, video, iframe, figure, table, ul, ol'
                ).length
                : 0;

        function elementLooksInteractive(el) {
            if (!el) {
                return false;
            }

            if (
                el.matches?.(
                    'a, button, nav, select, input, summary, [role="button"], [role="navigation"]'
                )
            ) {
                return true;
            }

            if (
                el.querySelector?.(
                    'a, button, select, input, [role="button"], iframe[src*="youtube"], video'
                )
            ) {
                return true;
            }

            const text =
                (el.textContent || '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();

            return /(^|\b)(next|previous|prev|download|play|watch|continue|read more|menu|tiếp|trước|tải xuống|phát|xem|đọc tiếp)(\b|$)/i
                .test(text);
        }

        function nearbyInteractiveRisk(ad) {
            if (!ad) {
                return false;
            }

            /*
             * Ads nested inside interactive UI are a strong structural risk.
             */
            if (
                ad.closest(
                    'a, button, nav, [role="button"], [role="navigation"]'
                )
            ) {
                return true;
            }

            const parent =
                ad.parentElement;

            if (!parent) {
                return false;
            }

            const siblings =
                [...parent.children];

            const index =
                siblings.indexOf(ad);

            if (index < 0) {
                return false;
            }

            /*
             * Look only at immediate neighboring blocks.
             * This is intentionally conservative and avoids pretending we can
             * measure rendered pixel distance from fetched HTML.
             */
            const nearby =
                siblings.slice(
                    Math.max(0, index - 1),
                    Math.min(
                        siblings.length,
                        index + 2
                    )
                );

            return nearby.some(
                el =>
                    el !== ad
                    && elementLooksInteractive(el)
            );
        }

        function misleadingLabelRisk(ad) {
            if (!ad) {
                return false;
            }

            let previous =
                ad.previousElementSibling;

            /*
             * Sometimes the ad is wrapped in a generic container.
             */
            if (
                !previous
                && ad.parentElement
            ) {
                previous =
                    ad.parentElement
                        .previousElementSibling;
            }

            if (!previous) {
                return false;
            }

            const label =
                (previous.textContent || '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();

            if (!label) {
                return false;
            }

            /*
             * Allowed/neutral labels are not warnings.
             */
            if (
                /^(advertisement|advertisements|sponsored links|quảng cáo)$/i
                    .test(label)
            ) {
                return false;
            }

            /*
             * Google specifically warns against headings that can make ads look
             * like navigation/resources/helpful links or encourage clicking.
             */
            return /(resources?|helpful links?|downloads?|click|support us|next|previous|menu|tài nguyên|liên kết hữu ích|tải xuống|bấm|nhấp|ủng hộ)/i
                .test(label);
        }

        const accidentalClickRisks =
            adElements.filter(
                nearbyInteractiveRisk
            ).length;

        const misleadingLabelRisks =
            adElements.filter(
                misleadingLabelRisk
            ).length;

        /*
         * V3.6.2 — show the result for every detected ad zone instead of
         * returning only a combined count.
         */
        adElements.forEach((ad, index) => {
            const interactiveRisk =
                nearbyInteractiveRisk(ad);

            const labelRisk =
                misleadingLabelRisk(ad);

            const name =
                describeAd(ad, index);

            if (
                interactiveRisk
                || labelRisk
            ) {
                const reasons = [];

                if (interactiveRisk) {
                    reasons.push(
                        'nằm trong/gần vùng tương tác'
                    );
                }

                if (labelRisk) {
                    reasons.push(
                        'nhãn/tiêu đề gần quảng cáo có thể gây hiểu nhầm'
                    );
                }

                add(
                    'warn',
                    'Ad slot — ' + name,
                    reasons.join('; ') + '.'
                );
            } else {
                add(
                    'pass',
                    'Ad slot — ' + name,
                    'Không phát hiện rủi ro cấu trúc rõ ràng ở slot này.'
                );
            }
        });

        /*
         * Google policy is about ads/promotional material exceeding
         * publisher-content. There is no official word-count/ad-count formula.
         * The following is an internal REVIEW heuristic only.
         */
        const possibleContentBalanceRisk =
            ads > 0
            && (
                publisherWords < 180
                || (
                    publisherBlocks > 0
                    && ads > publisherBlocks
                )
            );

        if (ads === 0) {
            add(
                'pass',
                'Bố trí quảng cáo',
                'Không phát hiện ad slot trên Preview. Không có vấn đề về mật độ quảng cáo ở trạng thái hiện tại.'
            );

        } else if (
            accidentalClickRisks > 0
            || misleadingLabelRisks > 0
        ) {
            add(
                'warn',
                'Bố trí quảng cáo cần xem lại',
                ads
                    + ' ad slot được phát hiện. '
                    + (
                        accidentalClickRisks > 0
                            ? accidentalClickRisks
                                + ' vị trí nằm trong/gần vùng tương tác; '
                            : ''
                    )
                    + (
                        misleadingLabelRisks > 0
                            ? misleadingLabelRisks
                                + ' vị trí có nhãn/tiêu đề dễ gây hiểu nhầm. '
                            : ''
                    )
                    + 'Hãy tách quảng cáo khỏi nút điều hướng, video/play, download, menu và các vùng dễ bấm nhầm.'
            );

        } else if (
            possibleContentBalanceRisk
        ) {
            add(
                'warn',
                'Cân bằng quảng cáo và nội dung cần xem lại',
                ads
                    + ' ad slot được phát hiện trong khi phần publisher-content có vẻ tương đối ít. '
                    + 'Google không cho phép quảng cáo hoặc nội dung quảng bá trả phí nhiều hơn publisher-content. '
                    + 'Đây là cảnh báo nội bộ, không phải ngưỡng số lượng chính thức của Google.'
            );

        } else {
            add(
                'pass',
                'Bố trí quảng cáo',
                ads
                    + ' ad slot được phát hiện. Không thấy dấu hiệu cấu trúc rõ ràng cho thấy quảng cáo lấn át publisher-content hoặc nằm sát vùng tương tác. '
                    + 'Số lượng ad slot tự nó không phải là vi phạm; vẫn nên kiểm tra trang thật sau khi Google phân phối quảng cáo.'
            );
        }

        return {
            rows,
            blocks,
            warnings
        };
    }


    /*
     * V3.6 PIXEL AD PLACEMENT AUDIT
     *
     * Google does not publish a universal minimum pixel distance between an
     * ad and every interactive element. The values below are conservative
     * INTERNAL heuristics only. The policy target is to avoid accidental
     * clicks, misleading placement, overlays, and ads interfering with user
     * interactions.
     *
     * The audit renders a SCRIPT-FREE copy of Preview inside an off-screen
     * iframe. Removing scripts prevents this audit from intentionally loading
     * Google ads in a hidden frame while still allowing CSS/layout measurement.
     */
    async function runPixelAdAudit() {
        const previewLink = getPreviewLink();

        if (!previewLink?.href) {
            return {
                rows: [
                    step(
                        'warn',
                        'Kiểm tra vị trí quảng cáo theo pixel',
                        'Không có Preview URL để đo bố cục thực tế.'
                    )
                ],
                blocks: 0,
                warnings: 1
            };
        }

        let html = '';

        try {
            const response = await fetch(previewLink.href, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) {
                return {
                    rows: [
                        step(
                            'warn',
                            'Kiểm tra vị trí quảng cáo theo pixel',
                            'Không tải được Preview để đo pixel (HTTP '
                                + response.status
                                + ').'
                        )
                    ],
                    blocks: 0,
                    warnings: 1
                };
            }

            html = await response.text();

        } catch (error) {
            return {
                rows: [
                    step(
                        'warn',
                        'Kiểm tra vị trí quảng cáo theo pixel',
                        'Không thể tải Preview để đo pixel.'
                    )
                ],
                blocks: 0,
                warnings: 1
            };
        }

        function buildSafeSrcdoc(rawHtml, baseUrl) {
            const parser = new DOMParser();
            const parsed = parser.parseFromString(rawHtml, 'text/html');

            /*
             * Do not execute page/ad scripts inside the audit frame.
             */
            parsed
                .querySelectorAll('script')
                .forEach(el => el.remove());

            /*
             * Preserve iframe boxes (YouTube/video placement) but do not load
             * the remote iframe itself during the hidden measurement.
             */
            parsed
                .querySelectorAll('iframe[src]')
                .forEach(frame => {
                    frame.setAttribute(
                        'data-pixel-audit-src',
                        frame.getAttribute('src') || ''
                    );

                    frame.removeAttribute('src');
                });

            /*
             * Remove refresh redirects if a page ever contains one.
             */
            parsed
                .querySelectorAll('meta[http-equiv="refresh" i]')
                .forEach(el => el.remove());

            const base =
                parsed.createElement('base');

            base.href =
                baseUrl;

            parsed.head.prepend(base);

            return '<!doctype html>\n'
                + parsed.documentElement.outerHTML;
        }

        function isVisible(win, el) {
            if (!el) {
                return false;
            }

            const style =
                win.getComputedStyle(el);

            const rect =
                el.getBoundingClientRect();

            return (
                style.display !== 'none'
                && style.visibility !== 'hidden'
                && Number(style.opacity || 1) > 0
                && rect.width > 2
                && rect.height > 2
            );
        }

        function rectGap(a, b) {
            const horizontal =
                Math.max(
                    a.left - b.right,
                    b.left - a.right,
                    0
                );

            const vertical =
                Math.max(
                    a.top - b.bottom,
                    b.top - a.bottom,
                    0
                );

            return Math.sqrt(
                horizontal * horizontal
                + vertical * vertical
            );
        }

        function overlaps(a, b) {
            return !(
                a.right <= b.left
                || a.left >= b.right
                || a.bottom <= b.top
                || a.top >= b.bottom
            );
        }

        function labelInteractive(el) {
            if (!el) {
                return 'vùng tương tác';
            }

            if (
                el.matches(
                    'iframe[data-pixel-audit-src*="youtube"], video, audio'
                )
            ) {
                return 'video / trình phát';
            }

            if (
                el.matches(
                    '.chapter-start, .chapter-list a'
                )
            ) {
                return 'điều hướng Chapter';
            }

            if (
                el.matches(
                    '.article-recommendations a'
                )
            ) {
                return 'Related Stories';
            }

            if (
                el.matches(
                    'nav, nav a, [role="navigation"], [role="navigation"] a'
                )
            ) {
                return 'menu / navigation';
            }

            if (
                el.matches(
                    'button, [role="button"], select, input, textarea, summary'
                )
            ) {
                return 'nút / điều khiển';
            }

            const text =
                (el.textContent || '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .slice(0, 60);

            return text
                ? '"' + text + '"'
                : 'liên kết / vùng tương tác';
        }

        function interactiveCandidates(doc, win) {
            const strongSelector = [
                'button',
                '[role="button"]',
                'select',
                'input',
                'textarea',
                'summary',
                'nav',
                'nav a',
                '[role="navigation"]',
                '[role="navigation"] a',
                '.chapter-start',
                '.chapter-list a',
                '.article-recommendations a',
                'iframe[data-pixel-audit-src*="youtube"]',
                'iframe[data-pixel-audit-src*="youtube-nocookie"]',
                'video',
                'audio'
            ].join(',');

            const strong =
                [...doc.querySelectorAll(strongSelector)];

            /*
             * Include text links only when their wording indicates a stronger
             * action/navigation intent. Ordinary editorial hyperlinks are not
             * automatically treated as accidental-click risks.
             */
            const actionLinks =
                [...doc.querySelectorAll('a[href]')]
                    .filter(link => {
                        const text =
                            (link.textContent || '')
                                .replace(/\s+/g, ' ')
                                .trim()
                                .toLowerCase();

                        return /(^|\b)(next|previous|prev|download|play|watch|continue|read more|menu|start chapter|tiếp|trước|tải xuống|phát|xem|đọc tiếp|bắt đầu)(\b|$)/i
                            .test(text);
                    });

            return [...new Set([
                ...strong,
                ...actionLinks
            ])]
                .filter(el =>
                    isVisible(
                        win,
                        el
                    )
                );
        }

        function adCandidates(doc, win) {
            /*
             * Prefer our policy wrapper because it represents the complete
             * visual ad zone and avoids double-counting the ad element inside.
             */
            const policyZones =
                [...doc.querySelectorAll(
                    '.policy-ad-zone'
                )]
                    .filter(el =>
                        isVisible(
                            win,
                            el
                        )
                    );

            if (policyZones.length) {
                return policyZones;
            }

            return [
                ...doc.querySelectorAll(
                    '[data-gam-slot], ins.adsbygoogle, .ad, [data-ad-slot], [data-ad-unit]'
                )
            ]
                .filter(el =>
                    isVisible(
                        win,
                        el
                    )
                );
        }

        async function auditViewport({
            width,
            height,
            name,
            warnDistance
        }) {
            const iframe =
                document.createElement('iframe');

            iframe.setAttribute(
                'aria-hidden',
                'true'
            );

            iframe.tabIndex = -1;

            Object.assign(
                iframe.style,
                {
                    position: 'fixed',
                    left: '-20000px',
                    top: '0',
                    width: width + 'px',
                    height: height + 'px',
                    border: '0',
                    opacity: '0',
                    pointerEvents: 'none',
                    zIndex: '-1'
                }
            );

            const safeHtml =
                buildSafeSrcdoc(
                    html,
                    previewLink.href
                );

            document.body.appendChild(
                iframe
            );

            try {
                await new Promise(
                    (resolve, reject) => {
                        const timeout =
                            setTimeout(
                                () => reject(
                                    new Error(
                                        'Pixel audit frame timeout'
                                    )
                                ),
                                10000
                            );

                        iframe.addEventListener(
                            'load',
                            () => {
                                clearTimeout(
                                    timeout
                                );

                                resolve();
                            },
                            {
                                once: true
                            }
                        );

                        iframe.srcdoc =
                            safeHtml;
                    }
                );

                const doc =
                    iframe.contentDocument;

                const win =
                    iframe.contentWindow;

                if (
                    !doc
                    || !win
                ) {
                    throw new Error(
                        'Pixel audit frame unavailable'
                    );
                }

                try {
                    if (
                        doc.fonts
                        && doc.fonts.ready
                    ) {
                        await Promise.race([
                            doc.fonts.ready,
                            new Promise(resolve =>
                                setTimeout(
                                    resolve,
                                    1500
                                )
                            )
                        ]);
                    }
                } catch (e) {}

                /*
                 * Give linked CSS and images a brief moment to affect layout.
                 */
                await new Promise(resolve =>
                    setTimeout(
                        resolve,
                        450
                    )
                );

                const ads =
                    adCandidates(
                        doc,
                        win
                    );

                const interactives =
                    interactiveCandidates(
                        doc,
                        win
                    );

                if (!ads.length) {
                    return {
                        level: 'pass',
                        title:
                            'Pixel Audit — '
                            + name,
                        detail:
                            'Không phát hiện ad slot hiển thị ở viewport '
                            + width
                            + 'px.',
                        blocks: 0,
                        warnings: 0
                    };
                }

                let minGap =
                    Infinity;

                let nearestLabel =
                    '';

                let overlapCount =
                    0;

                let overflowCount =
                    0;

                let fixedOverlapCount =
                    0;

                const slotDetails =
                    [];

                for (
                    let adIndex = 0;
                    adIndex < ads.length;
                    adIndex++
                ) {
                    const ad =
                        ads[adIndex];

                    const adRect =
                        ad.getBoundingClientRect();

                    const adName =
                        describeAd(
                            ad,
                            adIndex
                        );

                    let slotMinGap =
                        Infinity;

                    let slotNearestLabel =
                        '';

                    let slotOverlapCount =
                        0;

                    let slotOverflow =
                        false;

                    if (
                        adRect.left < -2
                        || adRect.right > width + 2
                    ) {
                        overflowCount++;
                        slotOverflow = true;
                    }

                    const adStyle =
                        win.getComputedStyle(
                            ad
                        );

                    for (
                        const interactive
                        of interactives
                    ) {
                        if (
                            ad.contains(
                                interactive
                            )
                            || interactive.contains(
                                ad
                            )
                        ) {
                            continue;
                        }

                        const interactiveRect =
                            interactive
                                .getBoundingClientRect();

                        if (
                            overlaps(
                                adRect,
                                interactiveRect
                            )
                        ) {
                            overlapCount++;
                            slotOverlapCount++;

                            if (
                                ['fixed', 'sticky', 'absolute']
                                    .includes(
                                        adStyle.position
                                    )
                            ) {
                                fixedOverlapCount++;
                            }

                            minGap = 0;
                            slotMinGap = 0;

                            nearestLabel =
                                labelInteractive(
                                    interactive
                                );

                            slotNearestLabel =
                                nearestLabel;

                            continue;
                        }

                        const gap =
                            rectGap(
                                adRect,
                                interactiveRect
                            );

                        if (
                            gap < minGap
                        ) {
                            minGap =
                                gap;

                            nearestLabel =
                                labelInteractive(
                                    interactive
                                );
                        }

                        if (
                            gap < slotMinGap
                        ) {
                            slotMinGap =
                                gap;

                            slotNearestLabel =
                                labelInteractive(
                                    interactive
                                );
                        }
                    }

                    let slotLevel =
                        'pass';

                    if (
                        slotOverlapCount > 0
                    ) {
                        slotLevel =
                            'block';

                    } else if (
                        slotOverflow
                        || (
                            Number.isFinite(
                                slotMinGap
                            )
                            && slotMinGap < warnDistance
                        )
                    ) {
                        slotLevel =
                            'warn';
                    }

                    slotDetails.push({
                        level:
                            slotLevel,
                        title:
                            'Pixel '
                            + name
                            + ' — '
                            + adName,
                        detail:
                            slotOverlapCount > 0
                                ? (
                                    'Phát hiện '
                                    + slotOverlapCount
                                    + ' chồng lấn với vùng tương tác'
                                    + (
                                        slotNearestLabel
                                            ? ' (gần nhất: '
                                                + slotNearestLabel
                                                + ')'
                                            : ''
                                    )
                                    + '.'
                                )
                                : (
                                    slotOverflow
                                        ? (
                                            'Ad slot có dấu hiệu tràn ngang viewport '
                                            + width
                                            + 'px.'
                                        )
                                        : (
                                            Number.isFinite(
                                                slotMinGap
                                            )
                                                ? (
                                                    'Khoảng cách gần nhất tới vùng tương tác khoảng '
                                                    + Math.round(
                                                        slotMinGap
                                                    )
                                                    + 'px'
                                                    + (
                                                        slotNearestLabel
                                                            ? ' ('
                                                                + slotNearestLabel
                                                                + ')'
                                                            : ''
                                                    )
                                                    + '.'
                                                )
                                                : 'Không phát hiện vùng tương tác gần để đo.'
                                        )
                                )
                    });
                }

                let level =
                    'pass';

                let blocks =
                    0;

                let warnings =
                    0;

                if (
                    overlapCount > 0
                    || fixedOverlapCount > 0
                ) {
                    level =
                        'block';

                    blocks =
                        1;

                } else if (
                    overflowCount > 0
                    || (
                        Number.isFinite(
                            minGap
                        )
                        && minGap < warnDistance
                    )
                ) {
                    level =
                        'warn';

                    warnings =
                        1;
                }

                return {
                    level,
                    title:
                        'Pixel Audit — '
                        + name,
                    detail:
                        ads.length
                        + ' ad slot; '
                        + (
                            overlapCount > 0
                                ? (
                                    'phát hiện '
                                    + overlapCount
                                    + ' trường hợp chồng lấn với vùng tương tác.'
                                )
                                : (
                                    overflowCount > 0
                                        ? (
                                            overflowCount
                                            + ' ad slot có dấu hiệu tràn ngang viewport.'
                                        )
                                        : (
                                            Number.isFinite(
                                                minGap
                                            )
                                                ? (
                                                    'khoảng cách gần nhất khoảng '
                                                    + Math.round(
                                                        minGap
                                                    )
                                                    + 'px'
                                                    + (
                                                        nearestLabel
                                                            ? ' ('
                                                                + nearestLabel
                                                                + ')'
                                                            : ''
                                                    )
                                                    + '.'
                                                )
                                                : 'không phát hiện khoảng cách rủi ro rõ ràng.'
                                        )
                                )
                        ),
                    blocks,
                    warnings,
                    slotDetails
                };

            } finally {
                iframe.remove();
            }
        }

        const viewports = [
            {
                width: 1366,
                height: 900,
                name: 'Desktop 1366px',
                /*
                 * INTERNAL heuristic only; Google does not publish a universal
                 * 36px rule.
                 */
                warnDistance: 36
            },
            {
                width: 390,
                height: 844,
                name: 'Mobile 390px',
                /*
                 * Touch interfaces are more prone to accidental taps, so the
                 * internal warning distance is intentionally more conservative.
                 */
                warnDistance: 48
            }
        ];

        const rows = [];
        let blocks = 0;
        let warnings = 0;

        for (
            const viewport
            of viewports
        ) {
            try {
                const result =
                    await auditViewport(
                        viewport
                    );

                rows.push(
                    step(
                        result.level,
                        result.title,
                        result.detail
                    )
                );

                if (
                    Array.isArray(
                        result.slotDetails
                    )
                ) {
                    result.slotDetails.forEach(
                        slot => {
                            rows.push(
                                step(
                                    slot.level,
                                    slot.title,
                                    slot.detail
                                )
                            );
                        }
                    );
                }

                blocks +=
                    result.blocks;

                warnings +=
                    result.warnings;

            } catch (error) {
                rows.push(
                    step(
                        'warn',
                        'Pixel Audit — '
                            + viewport.name,
                        'Không thể hoàn tất phép đo pixel cho viewport này.'
                    )
                );

                warnings++;
            }
        }

        return {
            rows,
            blocks,
            warnings
        };
    }


    function renderLive(audit) {
        liveSection.style.display = '';
        liveResult.innerHTML = audit.rows.join('');
    }

    async function fullScan(options = {}) {
        resetUi();
        runBtn.disabled = true;
        const oldLabel = runBtn.textContent;
        runBtn.textContent = 'Đang chạy kiểm tra toàn bộ…';
        setStatus('ĐANG KIỂM TRA', 'review');

        try {
            clickExistingLocalChecker();

            const local = localScan();

            if (local.blocks.length) {
                local.blocks.forEach(message =>
                    addProgress('block', 'Kiểm tra cục bộ', message)
                );

                setStatus('RỦI RO CAO', 'risk');
                finalResult = 'HIGH_RISK';
                lastFingerprint = fingerprint();

                return;
            }

            addProgress(
                'pass',
                'Quét nội dung cục bộ',
                local.words +
                    ' words • ' +
                    local.images +
                    ' body image(s) • ' +
                    local.youtube +
                    ' YouTube embed(s).'
            );

            const localFieldChecks = [
                {
                    label: 'Title',
                    ok: !!(titleField?.value || '').trim(),
                    detail: (titleField?.value || '').trim() || 'Thiếu title.'
                },
                {
                    label: 'Opening excerpt',
                    ok: !!(excerptField?.value || '').trim(),
                    detail: (excerptField?.value || '').trim()
                        ? 'Đã có excerpt.'
                        : 'Thiếu excerpt.'
                },
                {
                    label: 'Category',
                    ok: !!categoryField?.value,
                    detail: categoryField?.value
                        ? 'Đã chọn category.'
                        : 'Chưa chọn category.'
                },
                {
                    label: 'SEO title',
                    ok: !!(seoTitleField?.value || '').trim(),
                    detail: (seoTitleField?.value || '').trim()
                        ? 'Đã có SEO title.'
                        : 'Thiếu SEO title.'
                },
                {
                    label: 'Meta description',
                    ok: !!(metaField?.value || '').trim(),
                    detail: (metaField?.value || '').trim()
                        ? 'Đã có meta description.'
                        : 'Thiếu meta description.'
                }
            ];

            localFieldChecks.forEach(check => {
                addProgress(
                    check.ok ? 'pass' : 'warn',
                    'Local — ' + check.label,
                    check.detail
                );
            });

            local.warnings.forEach(message =>
                addProgress('warn', 'Cần xem lại cục bộ', message)
            );

            if (!options.skipAutoSave) {
                addProgress(
                    'info',
                    'Lưu Draft an toàn',
                    'Đang tự động lưu phiên bản hiện tại dưới dạng Draft trước khi kiểm tra Preview…'
                );

                const saved = await safeAutoSaveDraft();

                if (saved.needsReload) {
                    sessionStorage.setItem(PENDING_KEY, '1');
                    window.location.href = saved.finalUrl;
                    return;
                }

                addProgress(
                    'pass',
                    'Lưu Draft an toàn',
                    'Phiên bản hiện tại đã được lưu dưới dạng Draft.'
                );
            } else {
                addProgress(
                    'pass',
                    'Lưu Draft an toàn',
                    'Bản Draft đã sẵn sàng để kiểm tra tự động.'
                );
            }

            const localAfterSave = localScan();

            addProgress(
                'info',
                'Gemini',
                'Đang đánh giá giá trị biên tập và các tín hiệu rủi ro chính sách…'
            );

            let ai = null;
            let aiOverall = 'NEED_REVIEW';
            let aiUnavailable = false;

            try {
                ai = await runAiReview(localAfterSave);
                renderAi(ai);

                aiOverall =
                    String(ai.overall || 'NEED_REVIEW').toUpperCase();

                if (aiOverall === 'PASS') {
                    addProgress('pass', 'Đánh giá chính sách bằng Gemini AI', 'PASS');
                } else if (aiOverall === 'HIGH_RISK') {
                    addProgress('block', 'Đánh giá chính sách bằng Gemini AI', 'HIGH RISK');
                } else {
                    addProgress('warn', 'Đánh giá chính sách bằng Gemini AI', 'NEED REVIEW');
                }

            } catch (aiError) {
                aiUnavailable = true;
                aiOverall = 'NEED_REVIEW';

                aiSection.style.display = '';
                aiResult.innerHTML = `
                    <div class="as31-card">
                        <strong>Gemini tạm thời không khả dụng</strong>
                        ${escapeHtml(aiError?.message || 'Gemini đang bận hoặc tạm thời không khả dụng.')}
                    </div>
                    <div class="as31-card">
                        <strong>Hệ thống vẫn tiếp tục</strong>
                        Preview/Live, Canonical, Ad Structure và Pixel Audit vẫn được chạy.
                        Kết quả cuối giữ NEED_REVIEW cho đến khi Gemini chạy thành công.
                    </div>
                `;

                addProgress(
                    'warn',
                    'Đánh giá chính sách bằng Gemini AI',
                    'Gemini tạm thời không khả dụng — tiếp tục các kiểm tra còn lại.'
                );
            }

            addProgress(
                'info',
                'Kiểm tra Preview / Live',
                'Đang kiểm tra trang Preview đã lưu…',
                'live-audit'
            );

            const live = await runLiveAudit();

            updateProgress(
                'live-audit',
                live.blocks > 0
                    ? 'block'
                    : (
                        live.warnings > 0
                            ? 'warn'
                            : 'pass'
                    ),
                'Kiểm tra Preview / Live',
                live.blocks > 0
                    ? live.blocks + ' blocking issue(s).'
                    : (
                        live.warnings > 0
                            ? live.warnings + ' review item(s).'
                            : 'PASS'
                    )
            );

            addProgress(
                'info',
                'Pixel Ad Placement Audit',
                'Đang đo vị trí quảng cáo thực tế trên Desktop và Mobile…',
                'pixel-audit'
            );

            const pixelAudit =
                await runPixelAdAudit();

            updateProgress(
                'pixel-audit',
                pixelAudit.blocks > 0
                    ? 'block'
                    : (
                        pixelAudit.warnings > 0
                            ? 'warn'
                            : 'pass'
                    ),
                'Pixel Ad Placement Audit',
                pixelAudit.blocks > 0
                    ? pixelAudit.blocks + ' blocking issue(s).'
                    : (
                        pixelAudit.warnings > 0
                            ? pixelAudit.warnings + ' review item(s).'
                            : 'PASS — Desktop 1366px + Mobile 390px'
                    )
            );

            live.rows.push(
                ...pixelAudit.rows
            );

            live.blocks +=
                pixelAudit.blocks;

            live.warnings +=
                pixelAudit.warnings;

            renderLive(live);

            const hasLocalWarnings = localAfterSave.warnings.length > 0;

            if (
                aiOverall === 'HIGH_RISK' ||
                live.blocks > 0
            ) {
                finalResult = 'HIGH_RISK';
                setStatus('RỦI RO CAO', 'risk');
                manualReviewRequired = true;
                manualWrap.classList.add('open');

            } else if (
                aiUnavailable ||
                aiOverall === 'NEED_REVIEW' ||
                live.warnings > 0 ||
                hasLocalWarnings
            ) {
                finalResult = 'NEED_REVIEW';
                setStatus(
                    aiUnavailable ? 'CẦN XEM LẠI — GEMINI BẬN' : 'CẦN XEM LẠI',
                    'review'
                );
                manualReviewRequired = true;
                manualWrap.classList.add('open');

            } else {
                finalResult = 'PASS';
                setStatus('ĐẠT', 'pass');
                manualReviewRequired = false;
            }

            lastFingerprint = fingerprint();

            sessionStorage.setItem(
                RESULT_KEY,
                JSON.stringify({
                    fingerprint: lastFingerprint,
                    finalResult
                })
            );

        } catch (error) {
            finalResult = 'HIGH_RISK';
            setStatus('LỖI KIỂM TRA', 'risk');

            addProgress(
                'block',
                'Kiểm tra toàn bộ đã dừng',
                error.message || 'Không thể hoàn tất kiểm tra an toàn.'
            );

        } finally {
            runBtn.disabled = false;
            runBtn.textContent = oldLabel;
        }
    }

    runBtn.addEventListener('click', () => {
        fullScan();
    });

    form.addEventListener('input', () => {
        const current = fingerprint();

        if (lastFingerprint && current !== lastFingerprint) {
            lastFingerprint = '';
            finalResult = '';
            manualBox.checked = false;

            setStatus('BÀI ĐÃ THAY ĐỔI', 'review');
        }
    });

    form.addEventListener('submit', event => {
        if (!statusSelect || statusSelect.value !== 'published') {
            return;
        }

        const current = fingerprint();

        if (!lastFingerprint || current !== lastFingerprint) {
            event.preventDefault();

            alert(
                'Hãy chạy KIỂM TRA ADSENSE TOÀN BỘ trên phiên bản hiện tại trước khi xuất bản.'
            );

            return;
        }

        if (finalResult === 'HIGH_RISK') {
            event.preventDefault();

            alert(
                'Bài này đang được đánh dấu RỦI RO CAO. Hãy xử lý các vấn đề được nêu rồi chạy kiểm tra lại trước khi xuất bản.'
            );

            return;
        }

        if (
            (finalResult === 'NEED_REVIEW' || manualReviewRequired) &&
            !manualBox.checked
        ) {
            event.preventDefault();

            manualWrap.classList.add('open');
            manualWrap.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            alert(
                'Hãy đọc các cảnh báo và tích ô xác nhận kiểm tra thủ công trước khi xuất bản.'
            );
        }
    });

    // Continue automatically after a brand-new article was safely auto-saved.
    if (sessionStorage.getItem(PENDING_KEY) === '1') {
        sessionStorage.removeItem(PENDING_KEY);

        setTimeout(() => {
            fullScan({
                skipAutoSave: true
            });
        }, 700);
    }
})();