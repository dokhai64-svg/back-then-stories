(() => {
    'use strict';

    const form = document.getElementById('articleForm');

    if (!form || form.dataset.adsenseOneClickV31 === '1') {
        return;
    }

    form.dataset.adsenseOneClickV31 = '1';

    const editor = document.getElementById('editor');
    const titleField = form.querySelector('[name="title"]');
    const excerptField = form.querySelector('[name="excerpt"]');
    const seoTitleField = form.querySelector('[name="seo_title"]');
    const metaField = form.querySelector('[name="meta_description"]');
    const categoryField = form.querySelector('[name="category_id"]');
    const statusSelect = form.querySelector('select[name="status"]');

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

    function listHtml(items) {
        if (!Array.isArray(items) || !items.length) {
            return '<div class="as31-sub">None reported.</div>';
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
                <div class="as31-title">One-Click AdSense Safety Check</div>
                <div class="as31-sub">
                    Local scan → safe Draft auto-save → Gemini policy review → Preview/Live audit → combined result.
                </div>
            </div>
            <span id="as31Status" class="as31-status">NOT RUN</span>
        </div>

        <button type="button" id="as31Run" class="as31-btn">
            ✨ RUN FULL ADSENSE CHECK
        </button>

        <div id="as31Progress" class="as31-progress"></div>

        <div id="as31AiSection" class="as31-section" style="display:none">
            <div class="as31-section-title">Gemini AI Policy Review</div>
            <div id="as31AiResult"></div>
        </div>

        <div id="as31LiveSection" class="as31-section" style="display:none">
            <div class="as31-section-title">Preview / Live Page Audit</div>
            <div id="as31LiveResult"></div>
        </div>

        <div id="as31Manual" class="as31-manual">
            <label>
                <input type="checkbox" id="as31ManualBox">
                <span>
                    I manually reviewed the warnings and confirm the article has original editorial value,
                    media rights have been reviewed, and no known policy issue is being ignored.
                </span>
            </label>
        </div>

        <div class="as31-note">
            This is a conservative editorial safety tool, not a Google approval certificate.
            It cannot prove copyright ownership, licensing, plagiarism, or future AdSense approval.
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

    function addProgress(level, title, detail) {
        progress.classList.add('open');
        progress.insertAdjacentHTML('beforeend', step(level, title, detail));
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
                <strong>Overall</strong>
                ${escapeHtml(data.overall || 'NEED_REVIEW')}
            </div>
            <div class="as31-card">
                <strong>Original value</strong>
                ${escapeHtml(data.original_value || '')}
            </div>
            <div class="as31-card">
                <strong>Replicated-content risk</strong>
                ${escapeHtml(data.replicated_content_risk || '')}
            </div>
            <div class="as31-card">
                <strong>Originality comparison</strong>
                ${escapeHtml(data.source_comparison || '')}
            </div>
            <div class="as31-card">
                <strong>YouTube / embed</strong>
                ${escapeHtml(data.youtube_embed_assessment || '')}
            </div>
            <div class="as31-card">
                <strong>Fact-check items</strong>
                ${listHtml(data.fact_check_items)}
            </div>
            <div class="as31-card">
                <strong>Media-rights items</strong>
                ${listHtml(data.media_rights_items)}
            </div>
            <div class="as31-card">
                <strong>Policy flags</strong>
                ${listHtml(data.policy_flags)}
            </div>
            <div class="as31-card">
                <strong>Required fixes</strong>
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

        if (/^https:\/\//i.test(canonical)) {
            add('pass', 'Canonical', canonical);
        } else {
            add('block', 'Canonical', 'Missing or invalid HTTPS canonical URL.');
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
                    'Rendered images',
                    broken.length + ' image(s) failed to load.'
                );
            } else {
                add(
                    'pass',
                    'Rendered images',
                    imageUrls.length + ' image(s) loaded successfully.'
                );
            }
        } else {
            add(
                'pass',
                'Rendered images',
                'No images detected. Images are not required.'
            );
        }

        const ads = doc.querySelectorAll(
            '[data-gam-slot], ins.adsbygoogle, .ad'
        ).length;

        add(
            'warn',
            'Final ad density',
            ads +
                ' ad/ad-placeholder element(s) detected. ' +
                'Human review is still required because actual served-ad density can change.'
        );

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
        runBtn.textContent = 'Running full check…';
        setStatus('RUNNING', 'review');

        try {
            clickExistingLocalChecker();

            const local = localScan();

            if (local.blocks.length) {
                local.blocks.forEach(message =>
                    addProgress('block', 'Local check', message)
                );

                setStatus('HIGH RISK', 'risk');
                finalResult = 'HIGH_RISK';
                lastFingerprint = fingerprint();

                return;
            }

            addProgress(
                'pass',
                'Local content scan',
                local.words +
                    ' words • ' +
                    local.images +
                    ' body image(s) • ' +
                    local.youtube +
                    ' YouTube embed(s).'
            );

            local.warnings.forEach(message =>
                addProgress('warn', 'Local review', message)
            );

            if (!options.skipAutoSave) {
                addProgress(
                    'info',
                    'Safe Draft',
                    'Saving current article as Draft automatically before Preview audit…'
                );

                const saved = await safeAutoSaveDraft();

                if (saved.needsReload) {
                    sessionStorage.setItem(PENDING_KEY, '1');
                    window.location.href = saved.finalUrl;
                    return;
                }

                addProgress(
                    'pass',
                    'Safe Draft',
                    'Current article version saved as Draft.'
                );
            } else {
                addProgress(
                    'pass',
                    'Safe Draft',
                    'Draft is ready for automatic review.'
                );
            }

            const localAfterSave = localScan();

            addProgress(
                'info',
                'Gemini',
                'Reviewing editorial value and policy-risk signals…'
            );

            const ai = await runAiReview(localAfterSave);

            renderAi(ai);

            const aiOverall =
                String(ai.overall || 'NEED_REVIEW').toUpperCase();

            if (aiOverall === 'PASS') {
                addProgress(
                    'pass',
                    'Gemini AI Policy Review',
                    'PASS'
                );
            } else if (aiOverall === 'HIGH_RISK') {
                addProgress(
                    'block',
                    'Gemini AI Policy Review',
                    'HIGH RISK'
                );
            } else {
                addProgress(
                    'warn',
                    'Gemini AI Policy Review',
                    'NEED REVIEW'
                );
            }

            addProgress(
                'info',
                'Preview / Live Audit',
                'Checking the saved Preview page…'
            );

            const live = await runLiveAudit();
            renderLive(live);

            if (live.blocks > 0) {
                addProgress(
                    'block',
                    'Preview / Live Audit',
                    live.blocks + ' blocking issue(s).'
                );
            } else if (live.warnings > 0) {
                addProgress(
                    'warn',
                    'Preview / Live Audit',
                    live.warnings + ' review item(s).'
                );
            } else {
                addProgress(
                    'pass',
                    'Preview / Live Audit',
                    'PASS'
                );
            }

            const hasLocalWarnings = localAfterSave.warnings.length > 0;

            if (
                aiOverall === 'HIGH_RISK' ||
                live.blocks > 0
            ) {
                finalResult = 'HIGH_RISK';
                setStatus('HIGH RISK', 'risk');
                manualReviewRequired = true;
                manualWrap.classList.add('open');

            } else if (
                aiOverall === 'NEED_REVIEW' ||
                live.warnings > 0 ||
                hasLocalWarnings
            ) {
                finalResult = 'NEED_REVIEW';
                setStatus('NEED REVIEW', 'review');
                manualReviewRequired = true;
                manualWrap.classList.add('open');

            } else {
                finalResult = 'PASS';
                setStatus('PASS', 'pass');
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
            setStatus('CHECK ERROR', 'risk');

            addProgress(
                'block',
                'Full check stopped',
                error.message || 'The full safety check could not finish.'
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

            setStatus('ARTICLE CHANGED', 'review');
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
                'Run FULL ADSENSE CHECK on the current article before publishing.'
            );

            return;
        }

        if (finalResult === 'HIGH_RISK') {
            event.preventDefault();

            alert(
                'This article is marked HIGH RISK. Fix the flagged issues and run the full check again before publishing.'
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
                'Review the warnings and tick the manual-review confirmation before publishing.'
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