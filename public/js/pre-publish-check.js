(() => {
  'use strict';

  const form = document.getElementById('articleForm');
  if (!form || form.dataset.prePublishCheckLoaded === '1') return;
  form.dataset.prePublishCheckLoaded = '1';

  const editor = document.getElementById('editor') || form.querySelector('[contenteditable="true"]');
  const statusSelect = form.querySelector('select[name="status"]');
  const titleField = form.querySelector('[name="title"]');
  const excerptField = document.getElementById('excerptField') || form.querySelector('[name="excerpt"]');
  const seoTitleField = document.getElementById('seoTitleField') || form.querySelector('[name="seo_title"]');
  const metaField = document.getElementById('metaDescriptionField') || form.querySelector('[name="meta_description"]');
  const categoryField = form.querySelector('[name="category_id"]');
  const featuredFile = form.querySelector('[name="featured_image_file"]');

  const PLACEHOLDERS = [
    'lorem ipsum','replace this','coming soon','under construction',
    'your publisher cms is ready','insert content here','sample text','todo:','[placeholder]'
  ];

  function injectStyles() {
    if (document.getElementById('prePublishCheckStyles')) return;
    const s = document.createElement('style');
    s.id = 'prePublishCheckStyles';
    s.textContent = `
      .ppc-box{margin:18px 0;padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff}
      .ppc-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
      .ppc-title{font-weight:800;font-size:14px}
      .ppc-badge{font-size:11px;font-weight:800;padding:5px 8px;border-radius:999px;background:#eef2f7;color:#475569}
      .ppc-badge.ready{background:#e9f8ef;color:#166534}.ppc-badge.review{background:#fff7d6;color:#854d0e}.ppc-badge.block{background:#feecec;color:#991b1b}
      .ppc-list{display:grid;gap:7px;margin:10px 0}
      .ppc-row{display:grid;grid-template-columns:20px 1fr;gap:7px;align-items:start;font-size:12px;line-height:1.4}
      .ppc-row strong{display:block;font-size:12px;margin-bottom:1px}
      .ppc-row.pass{color:#166534}.ppc-row.warn{color:#854d0e}.ppc-row.block{color:#991b1b}.ppc-row.info{color:#475569}
      .ppc-confirm{display:grid;gap:8px;padding-top:10px;border-top:1px solid #eee;margin-top:10px}
      .ppc-confirm label{display:flex;gap:8px;align-items:flex-start;font-size:12px;line-height:1.35}
      .ppc-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
      .ppc-run{border:0;border-radius:8px;background:#171717;color:#fff;padding:9px 12px;font-weight:700;cursor:pointer}
      .ppc-note{font-size:11px;color:#64748b;margin-top:9px;line-height:1.4}
      .ppc-stats{font-size:11px;color:#64748b;margin:5px 0 0}
    `;
    document.head.appendChild(s);
  }

  function html() {
    return editor ? editor.innerHTML : (form.querySelector('[name="body"]')?.value || '');
  }

  function textFromHtml(v) {
    const d = document.createElement('div');
    d.innerHTML = v || '';
    return (d.innerText || d.textContent || '').replace(/\s+/g,' ').trim();
  }

  function words(v) {
    const t = String(v || '').trim();
    return t ? t.split(/\s+/).filter(Boolean).length : 0;
  }

  function currentFeaturedImagePresent() {
    if (featuredFile?.files?.length) return true;
    const preview = document.getElementById('featuredImagePreview');
    if (preview?.getAttribute('src')) return true;
    const media = form.querySelector('[name="featured_media_id"]');
    if (media?.value) return true;
    const imported = form.querySelector('[name="featured_image_url"],[name="imported_featured_image_url"]');
    return !!imported?.value;
  }

  const check = (level,title,detail) => ({level,title,detail});

  function runChecks() {
    const bodyHtml = html();
    const bodyText = textFromHtml(bodyHtml);
    const wc = words(bodyText);
    const title = (titleField?.value || '').trim();
    const excerpt = (excerptField?.value || '').trim();
    const seoTitle = (seoTitleField?.value || '').trim();
    const meta = (metaField?.value || '').trim();

    const d = document.createElement('div');
    d.innerHTML = bodyHtml;
    const images = [...d.querySelectorAll('img')];
    const youtube = [...d.querySelectorAll('iframe')].filter(x =>
      /youtube\.com\/embed|youtube-nocookie\.com\/embed/i.test(x.getAttribute('src') || '')
    );

    const allText = [title,excerpt,bodyText,seoTitle,meta].join(' ').toLowerCase();
    const placeholders = PLACEHOLDERS.filter(p => allText.includes(p));
    const checks = [];

    if (!title) checks.push(check('block','Title is missing','Add a clear title before publishing.'));
    else if (title.length < 20) checks.push(check('warn','Title is very short',`${title.length} characters.`));
    else checks.push(check('pass','Title',`${title.length} characters.`));

    if (wc < 120) checks.push(check('block','Article body is too thin',`${wc} words. Add substantial publisher-written text.`));
    else if (wc < 500) checks.push(check('warn','Article depth',`${wc} words. There is no official AdSense word minimum, but this is relatively short for an editorial article.`));
    else checks.push(check('pass','Article depth',`${wc} words of body text.`));

    if (placeholders.length) checks.push(check('block','Placeholder text detected',`Found: ${placeholders.join(', ')}.`));
    else checks.push(check('pass','No obvious placeholder text','No common unfinished-page phrases detected.'));

    if (!excerpt) checks.push(check('warn','Opening excerpt is empty','Add a useful summary/deck.'));
    else checks.push(check(words(excerpt) >= 20 ? 'pass' : 'warn','Opening excerpt',`${words(excerpt)} words.`));

    if (!categoryField?.value) checks.push(check('warn','Category is not selected','Choose a relevant category if available.'));
    else checks.push(check('pass','Category selected','A category is assigned.'));

    if (!seoTitle) checks.push(check('warn','SEO title is empty','Recommended for search presentation.'));
    else checks.push(check(seoTitle.length >= 35 && seoTitle.length <= 70 ? 'pass' : 'warn','SEO title',`${seoTitle.length} characters.`));

    if (!meta) checks.push(check('warn','Meta description is empty','Recommended for search presentation.'));
    else checks.push(check(meta.length >= 120 && meta.length <= 170 ? 'pass' : 'warn','Meta description',`${meta.length} characters.`));

    if (youtube.length) {
      checks.push(check(
        wc >= 400 ? 'pass' : 'warn',
        'YouTube embed',
        wc >= 400
          ? `${youtube.length} embed(s); the article also contains substantial text.`
          : `${youtube.length} embed(s) with ${wc} words. Make sure video is supplementary, not the main value.`
      ));
    } else {
      checks.push(check('info','YouTube','No YouTube embed detected. This is completely fine.'));
    }

    const base64 = images.filter(i => /^data:image\//i.test(i.getAttribute('src') || ''));
    const external = images.filter(i => {
      const src = i.getAttribute('src') || '';
      return /^https?:\/\//i.test(src) && !src.startsWith(location.origin);
    });
    const emptyAlt = images.filter(i => !(i.getAttribute('alt') || '').trim());

    if (base64.length) checks.push(check('warn','Embedded base64 image detected',`${base64.length} image(s). Prefer Media Library/storage URLs.`));
    if (external.length) checks.push(check('warn','External image URL detected',`${external.length} image(s). Verify usage rights and source.`));

    if (images.length && emptyAlt.length) checks.push(check('warn','Image alt text',`${emptyAlt.length} of ${images.length} body image(s) have empty alt text.`));
    else if (images.length) checks.push(check('pass','Image alt text',`${images.length} body image(s) have alt text.`));

    if (images.length || currentFeaturedImagePresent()) {
      checks.push(check('info','Image rights require manual review','Confirm images are yours, licensed, public domain, or otherwise permitted.'));
    } else {
      checks.push(check('pass','No article images','No Featured Image/body image detected. Images are not required.'));
    }

    return {checks, wc, imageCount:images.length, youtubeCount:youtube.length};
  }

  function icon(level) {
    return level === 'pass' ? '✓' : level === 'block' ? '✕' : level === 'warn' ? '!' : 'i';
  }

  injectStyles();

  const sideCard = statusSelect?.closest('.card') || form.querySelector('aside .card') || form;
  const panel = document.createElement('div');
  panel.className = 'ppc-box';
  panel.innerHTML = `
    <div class="ppc-head">
      <div>
        <div class="ppc-title">Pre-Publish Check</div>
        <div class="ppc-stats" id="ppcStats">Run the check before publishing.</div>
      </div>
      <span class="ppc-badge" id="ppcBadge">NOT CHECKED</span>
    </div>
    <div class="ppc-list" id="ppcList"></div>
    <div class="ppc-confirm">
      <label><input type="checkbox" id="ppcOriginality"><span>I reviewed the article and it adds original editorial value; it is not just a copied transcript or light rewrite.</span></label>
      <label><input type="checkbox" id="ppcRights"><span>I reviewed image/media rights, or this article contains no images requiring permission.</span></label>
    </div>
    <div class="ppc-actions"><button type="button" class="ppc-run" id="ppcRun">Run check</button></div>
    <div class="ppc-note">Editorial safety check only — not a guarantee of AdSense approval or legal clearance.</div>
  `;

  const saveButton = [...sideCard.querySelectorAll('button')].find(b => /save article/i.test(b.textContent || ''));
  if (saveButton) sideCard.insertBefore(panel, saveButton);
  else sideCard.appendChild(panel);

  const list = document.getElementById('ppcList');
  const badge = document.getElementById('ppcBadge');
  const stats = document.getElementById('ppcStats');
  const originality = document.getElementById('ppcOriginality');
  const rights = document.getElementById('ppcRights');
  let lastResult = null;

  function render() {
    lastResult = runChecks();
    list.innerHTML = '';

    lastResult.checks.forEach(c => {
      const row = document.createElement('div');
      row.className = `ppc-row ${c.level}`;
      row.innerHTML = `<div>${icon(c.level)}</div><div><strong>${c.title}</strong><span>${c.detail}</span></div>`;
      list.appendChild(row);
    });

    const blocks = lastResult.checks.filter(c => c.level === 'block').length;
    const warnings = lastResult.checks.filter(c => c.level === 'warn').length;

    stats.textContent = `${lastResult.wc} words • ${lastResult.imageCount} body image(s) • ${lastResult.youtubeCount} YouTube embed(s)`;

    if (blocks) {
      badge.textContent = `${blocks} BLOCK`;
      badge.className = 'ppc-badge block';
    } else if (warnings) {
      badge.textContent = `${warnings} REVIEW`;
      badge.className = 'ppc-badge review';
    } else {
      badge.textContent = 'READY';
      badge.className = 'ppc-badge ready';
    }
    return {blocks,warnings};
  }

  document.getElementById('ppcRun').addEventListener('click', render);

  let timer = null;
  form.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => { if (lastResult) render(); }, 500);
  });

  form.addEventListener('submit', event => {
    if (!statusSelect || statusSelect.value !== 'published') return;

    const {blocks,warnings} = render();

    if (!originality.checked || !rights.checked) {
      event.preventDefault();
      panel.scrollIntoView({behavior:'smooth',block:'center'});
      alert('Before publishing, confirm both checks:\n\n• Original editorial value\n• Image/media rights reviewed (or no images)');
      return;
    }

    if (blocks > 0) {
      event.preventDefault();
      panel.scrollIntoView({behavior:'smooth',block:'center'});
      alert('Publishing blocked. Fix the red Pre-Publish Check items first.');
      return;
    }

    if (warnings > 0 && !confirm(
      `Pre-Publish Check found ${warnings} warning(s).\n\nWarnings are not automatic policy violations, but they should be reviewed.\n\nPublish anyway?`
    )) {
      event.preventDefault();
      panel.scrollIntoView({behavior:'smooth',block:'center'});
    }
  });

  render();
})();
