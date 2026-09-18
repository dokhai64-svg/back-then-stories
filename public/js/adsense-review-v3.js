(() => {
    'use strict';

    const form = document.getElementById('articleForm');
    if (!form || form.dataset.adsenseReviewV3 === '1') {
        return;
    }

    form.dataset.adsenseReviewV3 = '1';

    const editor = document.getElementById('editor');
    const statusSelect = form.querySelector('select[name="status"]');
    const titleField = form.querySelector('[name="title"]');
    const excerptField = form.querySelector('[name="excerpt"]');
    const previewLink = [...document.querySelectorAll('a[target="_blank"]')]
        .find(a => /preview/i.test(a.textContent || ''));

    const aiUrl = '/admin/articles/adsense-review';

    let aiReviewedFingerprint = '';
    let aiOverall = '';
    let liveAuditedFingerprint = '';
    let liveBlocks = 0;
    let liveWarnings = 0;

    function injectStyles() {
        if (document.getElementById('adsenseReviewV3Styles')) return;

        const style = document.createElement('style');
        style.id = 'adsenseReviewV3Styles';
        style.textContent = `
            .arv3-box{margin:18px 0;padding:14px;border:1px solid #dbe3ec;border-radius:12px;background:#fff}
            .arv3-title{font-size:14px;font-weight:850}
            .arv3-sub{font-size:10px;color:#64748b;line-height:1.4;margin-top:3px}
            .arv3-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:12px}
            .arv3-btn{border:0;border-radius:8px;padding:9px 11px;font-size:11px;font-weight:800;cursor:pointer;background:#171717;color:#fff}
            .arv3-btn.secondary{background:#eef2f7;color:#334155;border:1px solid #dbe3ec}
            .arv3-btn:disabled{opacity:.6;cursor:wait}
            .arv3-source{display:none;margin-top:10px}
            .arv3-source.open{display:block}
            .arv3-source textarea{width:100%;min-height:110px;font-size:11px}
            .arv3-result{margin-top:12px}
            .arv3-badge{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:10px;font-weight:850;background:#eef2f7;color:#475569}
            .arv3-badge.pass{background:#e9f8ef;color:#166534}
            .arv3-badge.review{background:#fff7d6;color:#854d0e}
            .arv3-badge.risk{background:#feecec;color:#991b1b}
            .arv3-grid{display:grid;gap:8px;margin-top:9px}
            .arv3-card{padding:9px 10px;border:1px solid #e5e7eb;border-radius:8px;background:#fafafa;font-size:11px;line-height:1.45}
            .arv3-card strong{display:block;margin-bottom:3px}
            .arv3-list{margin:5px 0 0 16px;padding:0}
            .arv3-list li{margin:3px 0}
            .arv3-live-row{display:grid;grid-template-columns:18px 1fr;gap:7px;font-size:11px;line-height:1.4;margin:6px 0}
            .arv3-live-row.pass{color:#166534}
            .arv3-live-row.warn{color:#854d0e}
            .arv3-live-row.block{color:#991b1b}
            .arv3-note{font-size:10px;color:#64748b;line-height:1.45;margin-top:10px}
            .arv3-check{display:none;margin-top:9px;padding-top:9px;border-top:1px solid #eee}
            .arv3-check.open{display:block}
            .arv3-check label{display:flex;gap:7px;align-items:flex-start;font-size:11px;line-height:1.4}
            .arv3-check input{width:auto;margin-top:2px}
        `;

        document.head.appendChild(style);
    }

    function getBodyHtml() {
        return editor?.innerHTML || '';
    }

    function fingerprint() {
        const value = [
            titleField?.value || '',
            excerptField?.value || '',
            getBodyHtml()
        ].join('\n');

        let hash = 2166136261;

        for (let i = 0; i < value.length; i++) {
            hash ^= value.charCodeAt(i);
            hash = Math.imul(hash, 16777619);
        }

        return String(hash >>> 0);
    }

    function bodyStats() {
        const container = document.createElement('div');
        container.innerHTML = getBodyHtml();

        return {
            youtube: [...container.querySelectorAll('iframe')]
                .filter(x => /youtube\.com\/embed|youtube-nocookie\.com\/embed/i.test(x.src || '')).length,
            images: container.querySelectorAll('img').length,
        };
    }

    function makeList(items) {
        if (!Array.isArray(items) || !items.length) {
            return '<div class="arv3-sub">None reported.</div>';
        }

        return '<ul class="arv3-list">' +
            items.map(item => '<li>' + escapeHtml(item) + '</li>').join('') +
            '</ul>';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function invalidateResults() {
        if (aiReviewedFingerprint && aiReviewedFingerprint !== fingerprint()) {
            aiReviewedFingerprint = '';
            aiOverall = '';
            aiStatus.textContent = 'Article changed — run AI review again.';
            aiStatus.className = 'arv3-badge review';
        }

        if (liveAuditedFingerprint && liveAuditedFingerprint !== fingerprint()) {
            liveAuditedFingerprint = '';
            liveStatus.textContent = 'Article changed — run Live Audit again.';
            liveStatus.className = 'arv3-badge review';
        }
    }

    injectStyles();

    const sideCard = statusSelect?.closest('.card') || form.querySelector('aside .card') || form;
    const saveButton = [...sideCard.querySelectorAll('button')]
        .find(btn => /save article|lưu bài viết/i.test(btn.textContent || ''));

    const panel = document.createElement('div');
    panel.className = 'arv3-box';
    panel.innerHTML = `
        <div class="arv3-title">AdSense Safety V3</div>
        <div class="arv3-sub">
            Layer 2: Gemini AI Policy Review · Layer 3: Preview / Live Page Audit.
            Local Checker V2 remains the first layer.
        </div>

        <div class="arv3-actions">
            <button type="button" id="arv3AiBtn" class="arv3-btn">✨ AI AdSense Review</button>
            <button type="button" id="arv3SourceBtn" class="arv3-btn secondary">Source / Transcript (optional)</button>
            <button type="button" id="arv3LiveBtn" class="arv3-btn secondary">✓ Live Page Audit</button>
        </div>

        <div id="arv3Source" class="arv3-source">
            <textarea id="arv3SourceText" placeholder="Optional: paste the source article or YouTube transcript here. Gemini can then compare the article against the source for light-rewrite / transcript risk."></textarea>
        </div>

        <div class="arv3-result">
            <strong style="font-size:11px">AI Policy Review</strong>
            <div style="margin-top:6px"><span id="arv3AiStatus" class="arv3-badge">NOT RUN</span></div>
            <div id="arv3AiResult" class="arv3-grid"></div>
        </div>

        <div class="arv3-result">
            <strong style="font-size:11px">Preview / Live Page Audit</strong>
            <div style="margin-top:6px"><span id="arv3LiveStatus" class="arv3-badge">NOT RUN</span></div>
            <div id="arv3LiveResult" style="margin-top:8px"></div>
        </div>

        <div id="arv3ManualReview" class="arv3-check">
            <label>
                <input type="checkbox" id="arv3ManualReviewBox">
                <span>I reviewed the AI / Live Audit warnings and accept responsibility for the final publish decision.</span>
            </label>
        </div>

        <div class="arv3-note">
            V3 is advisory. Gemini cannot certify AdSense approval, plagiarism, copyright ownership, or image licensing.
            HIGH RISK is intentionally conservative and must be fixed/reviewed before publishing.
        </div>
    `;

    if (saveButton) {
        sideCard.insertBefore(panel, saveButton);
    } else {
        sideCard.appendChild(panel);
    }

    const aiBtn = document.getElementById('arv3AiBtn');
    const sourceBtn = document.getElementById('arv3SourceBtn');
    const liveBtn = document.getElementById('arv3LiveBtn');
    const sourceWrap = document.getElementById('arv3Source');
    const sourceText = document.getElementById('arv3SourceText');
    const aiStatus = document.getElementById('arv3AiStatus');
    const aiResult = document.getElementById('arv3AiResult');
    const liveStatus = document.getElementById('arv3LiveStatus');
    const liveResult = document.getElementById('arv3LiveResult');
    const manualWrap = document.getElementById('arv3ManualReview');
    const manualBox = document.getElementById('arv3ManualReviewBox');

    sourceBtn.addEventListener('click', () => {
        sourceWrap.classList.toggle('open');
    });

    form.addEventListener('input', () => {
        invalidateResults();
        manualBox.checked = false;
    });

    aiBtn.addEventListener('click', async () => {
        const title = (titleField?.value || '').trim();
        const body = getBodyHtml();

        if (!title) {
            alert('Enter the article title first.');
            return;
        }

        const plain = document.createElement('div');
        plain.innerHTML = body;

        if ((plain.innerText || '').trim().length < 120) {
            alert('Add a fuller article body before running AI AdSense Review.');
            return;
        }

        const stats = bodyStats();

        aiBtn.disabled = true;
        const oldLabel = aiBtn.textContent;
        aiBtn.textContent = 'Reviewing…';
        aiStatus.textContent = 'RUNNING';
        aiStatus.className = 'arv3-badge review';
        aiResult.innerHTML = '';

        try {
            const response = await fetch(aiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    title,
                    body,
                    source_text: sourceText.value || '',
                    youtube_count: stats.youtube,
                    image_count: stats.images
                })
            });

            let data = {};

            try {
                data = await response.json();
            } catch (e) {
                data = {};
            }

            if (!response.ok) {
                throw new Error(data.message || 'AI AdSense Review failed.');
            }

            aiOverall = data.overall || 'NEED_REVIEW';
            aiReviewedFingerprint = fingerprint();

            const cls =
                aiOverall === 'PASS' ? 'pass' :
                aiOverall === 'HIGH_RISK' ? 'risk' :
                'review';

            aiStatus.textContent = aiOverall;
            aiStatus.className = 'arv3-badge ' + cls;

            aiResult.innerHTML = `
                <div class="arv3-card"><strong>Original value</strong>${escapeHtml(data.original_value)}</div>
                <div class="arv3-card"><strong>Replicated-content risk</strong>${escapeHtml(data.replicated_content_risk)}</div>
                <div class="arv3-card"><strong>Source comparison</strong>${escapeHtml(data.source_comparison)}</div>
                <div class="arv3-card"><strong>YouTube / embed</strong>${escapeHtml(data.youtube_embed_assessment)}</div>
                <div class="arv3-card"><strong>Fact-check items</strong>${makeList(data.fact_check_items)}</div>
                <div class="arv3-card"><strong>Media-rights items</strong>${makeList(data.media_rights_items)}</div>
                <div class="arv3-card"><strong>Policy flags</strong>${makeList(data.policy_flags)}</div>
                <div class="arv3-card"><strong>Required fixes</strong>${makeList(data.required_fixes)}</div>
                <div class="arv3-card"><strong>Review note</strong>${escapeHtml(data.review_note)}</div>
            `;

            if (aiOverall !== 'PASS') {
                manualWrap.classList.add('open');
            }

        } catch (error) {
            aiStatus.textContent = 'ERROR';
            aiStatus.className = 'arv3-badge risk';
            aiResult.innerHTML =
                '<div class="arv3-card"><strong>Error</strong>' +
                escapeHtml(error.message || 'AI review failed.') +
                '</div>';
        } finally {
            aiBtn.disabled = false;
            aiBtn.textContent = oldLabel;
        }
    });

    function liveRow(level, title, detail) {
        const symbol = level === 'pass' ? '✓' : level === 'block' ? '✕' : '!';
        return `
            <div class="arv3-live-row ${level}">
                <div>${symbol}</div>
                <div><strong>${escapeHtml(title)}</strong><br>${escapeHtml(detail)}</div>
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

    liveBtn.addEventListener('click', async () => {
        if (!previewLink?.href) {
            alert('Save this article as Draft first. V3 requires a real Preview URL before publishing.');
            return;
        }

        liveBtn.disabled = true;
        const oldLabel = liveBtn.textContent;
        liveBtn.textContent = 'Auditing…';
        liveStatus.textContent = 'RUNNING';
        liveStatus.className = 'arv3-badge review';
        liveResult.innerHTML = '';

        const rows = [];
        liveBlocks = 0;
        liveWarnings = 0;

        const add = (level, title, detail) => {
            rows.push(liveRow(level, title, detail));
            if (level === 'block') liveBlocks++;
            if (level === 'warn') liveWarnings++;
        };

        try {
            const response = await fetch(previewLink.href, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) {
                add('block', 'Preview HTTP status', `Preview returned HTTP ${response.status}.`);
                throw new Error('Preview page could not be loaded.');
            }

            add('pass', 'Preview HTTP status', `HTTP ${response.status}.`);

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            const title = (doc.querySelector('title')?.textContent || '').trim();
            if (title) {
                add('pass', 'Page title', title);
            } else {
                add('block', 'Page title', 'No <title> was rendered.');
            }

            const canonical = doc.querySelector('link[rel="canonical"]')?.getAttribute('href') || '';
            if (/^https:\/\//i.test(canonical)) {
                add('pass', 'Canonical', canonical);
            } else {
                add('block', 'Canonical', 'Missing or invalid HTTPS canonical URL.');
            }

            const articleBody =
                doc.querySelector('.article .body') ||
                doc.querySelector('.body') ||
                doc.querySelector('main');

            const text = (articleBody?.textContent || '').replace(/\s+/g, ' ').trim();

            if (text.length < 120) {
                add('block', 'Rendered publisher content', 'Very little article text rendered on Preview.');
            } else {
                add('pass', 'Rendered publisher content', `${text.split(/\s+/).filter(Boolean).length} rendered words.`);
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
                add('block', 'Placeholder content', `Found: ${placeholders.join(', ')}`);
            } else {
                add('pass', 'Placeholder content', 'No common unfinished-page text detected.');
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
                            return new URL(a.getAttribute('href'), previewLink.href).pathname === path;
                        } catch (e) {
                            return false;
                        }
                    });

                add(
                    found ? 'pass' : 'warn',
                    `${label} link`,
                    found ? 'Present on rendered page.' : `No ${path} link found on this rendered page.`
                );
            }

            const iframes = [...doc.querySelectorAll('iframe')];
            const youtube = iframes.filter(x =>
                /youtube\.com\/embed|youtube-nocookie\.com\/embed/i.test(x.getAttribute('src') || '')
            );

            const badYouTube = youtube.filter(x =>
                !/^https:\/\/(www\.)?youtube(-nocookie)?\.com\/embed\/[A-Za-z0-9_-]+/i
                    .test(x.getAttribute('src') || '')
            );

            if (badYouTube.length) {
                add('block', 'YouTube embed', `${badYouTube.length} invalid YouTube iframe URL(s).`);
            } else if (youtube.length) {
                add('pass', 'YouTube embed', `${youtube.length} valid YouTube iframe(s) rendered.`);
            } else {
                add('pass', 'YouTube embed', 'No YouTube iframe required/detected.');
            }

            const images = [...doc.querySelectorAll('img[src]')];
            const resolvedImages = images
                .map(img => {
                    try {
                        return new URL(img.getAttribute('src'), previewLink.href).href;
                    } catch (e) {
                        return '';
                    }
                })
                .filter(Boolean)
                .slice(0, 30);

            if (resolvedImages.length) {
                const checks = await Promise.all(resolvedImages.map(checkImage));
                const broken = checks.filter(x => !x.ok);

                if (broken.length) {
                    add('block', 'Rendered images', `${broken.length} image(s) failed to load.`);
                } else {
                    add('pass', 'Rendered images', `${resolvedImages.length} image(s) loaded successfully.`);
                }
            } else {
                add('pass', 'Rendered images', 'No article/page images detected. Images are not required.');
            }

            const ads = doc.querySelectorAll(
                '[data-gam-slot], ins.adsbygoogle, .ad'
            ).length;

            add(
                'warn',
                'Ad density needs human review',
                `${ads} ad/ad-placeholder element(s) detected. Google requires ads/paid promotion not to exceed publisher-content; this checker cannot measure final served-ad density perfectly.`
            );

            liveAuditedFingerprint = fingerprint();

            if (liveBlocks > 0) {
                liveStatus.textContent = `${liveBlocks} FIX FIRST`;
                liveStatus.className = 'arv3-badge risk';
            } else if (liveWarnings > 0) {
                liveStatus.textContent = `${liveWarnings} REVIEW`;
                liveStatus.className = 'arv3-badge review';
                manualWrap.classList.add('open');
            } else {
                liveStatus.textContent = 'PASS';
                liveStatus.className = 'arv3-badge pass';
            }

        } catch (error) {
            if (!liveBlocks) {
                liveBlocks = 1;
            }

            rows.push(liveRow(
                'block',
                'Live Audit failed',
                error.message || 'Could not audit Preview page.'
            ));

            liveStatus.textContent = 'ERROR';
            liveStatus.className = 'arv3-badge risk';
        } finally {
            liveResult.innerHTML = rows.join('');
            liveBtn.disabled = false;
            liveBtn.textContent = oldLabel;
        }
    });

    form.addEventListener('submit', event => {
        if (!statusSelect || statusSelect.value !== 'published') {
            return;
        }

        invalidateResults();

        const current = fingerprint();

        if (!previewLink?.href) {
            event.preventDefault();
            alert(
                'V3 safety workflow: save the article as Draft first, then run AI AdSense Review + Live Page Audit before publishing.'
            );
            return;
        }

        if (aiReviewedFingerprint !== current) {
            event.preventDefault();
            alert('Run AI AdSense Review on the current article before publishing.');
            return;
        }

        if (liveAuditedFingerprint !== current) {
            event.preventDefault();
            alert('Run Live Page Audit on the current article before publishing.');
            return;
        }

        if (aiOverall === 'HIGH_RISK') {
            event.preventDefault();
            alert(
                'AI AdSense Review marked this article HIGH RISK. Keep it Draft/Review, fix the flagged issues, then run the review again.'
            );
            return;
        }

        if (liveBlocks > 0) {
            event.preventDefault();
            alert('Live Page Audit found blocking issues. Fix them before publishing.');
            return;
        }

        if ((aiOverall === 'NEED_REVIEW' || liveWarnings > 0) && !manualBox.checked) {
            event.preventDefault();
            manualWrap.classList.add('open');
            manualWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
            alert('Review the V3 warnings and tick the manual-review confirmation before publishing.');
        }
    });
})();