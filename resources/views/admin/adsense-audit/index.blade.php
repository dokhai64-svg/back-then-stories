@extends('admin.layout')

@section('title', 'AdSense Site-wide Audit')

@section('content')
<style>
.audit-wrap{max-width:1180px;margin:0 auto}.audit-hero,.audit-card{border:1px solid #dde3ea;border-radius:14px;background:#fff}.audit-hero{padding:20px;margin-bottom:18px}.audit-title{margin:0;font-size:24px}.audit-sub{margin:8px 0 0;color:#64748b;font-size:13px;line-height:1.5}.audit-run{display:inline-flex;align-items:center;justify-content:center;min-height:44px;margin-top:16px;padding:0 18px;border:0;border-radius:9px;background:#111;color:#fff;font-weight:800;cursor:pointer}.audit-run:disabled{opacity:.55;cursor:wait}.audit-status{display:none;margin-top:14px;padding:12px;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa;font-size:12px}.audit-status.open{display:block}.audit-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.audit-stat{padding:16px;border:1px solid #dde3ea;border-radius:12px;background:#fff}.audit-stat b{display:block;margin-top:5px;font-size:24px}.audit-badge{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:900}.audit-badge.ready,.audit-level-pass{color:#166534;background:#e9f8ef}.audit-badge.review,.audit-level-warn{color:#854d0e;background:#fff7d6}.audit-badge.block,.audit-level-block{color:#991b1b;background:#feecec}.audit-level-info{color:#475569;background:#eef2f7}.audit-card{padding:18px;margin-bottom:18px}.audit-card h2{margin:0 0 14px;font-size:18px}.audit-checks{display:grid;gap:8px}.audit-check{display:grid;grid-template-columns:94px 1fr;gap:10px;padding:9px 10px;border:1px solid #edf0f4;border-radius:9px}.audit-level{display:inline-flex;align-items:center;justify-content:center;min-height:25px;padding:0 8px;border-radius:999px;font-size:10px;font-weight:900}.audit-check strong{display:block;font-size:12px}.audit-check p{margin:3px 0 0;color:#596579;font-size:11px}.audit-table-wrap{overflow:auto}.audit-table{width:100%;border-collapse:collapse;font-size:12px}.audit-table th,.audit-table td{padding:10px 9px;border-bottom:1px solid #edf0f4;text-align:left;vertical-align:top}.audit-table th{color:#64748b;font-size:10px;text-transform:uppercase}.audit-table a{color:#2563eb;text-decoration:none}.audit-details{margin-top:8px}.audit-details summary{cursor:pointer;font-weight:700;font-size:11px}.audit-article-checks{display:grid;gap:6px;margin-top:8px}.audit-article-check{padding:7px 8px;border-radius:7px;background:#f8fafc;font-size:10px}.audit-ai{margin-top:7px;padding:8px;border-radius:8px;background:#f8fafc;font-size:10px}.audit-ai.pass{color:#166534;background:#eefbf2}.audit-ai.review{color:#854d0e;background:#fff9e6}.audit-ai.risk{color:#991b1b;background:#fff0f0}@media(max-width:800px){.audit-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="audit-wrap">
    <section class="audit-hero">
        <h1 class="audit-title">AdSense Site-wide Audit V4.1</h1>
        <p class="audit-sub">
            Rule-based scan toàn bộ Published Articles → tự chọn bài có rủi ro nội dung →
            Gemini AI quét riêng các bài đó → tổng hợp READY / NEED REVIEW / BLOCK.
        </p>
        <button type="button" id="runSiteAudit" class="audit-run">
            🔍 RUN SITE-WIDE ADSENSE AUDIT
        </button>
        <div id="auditProgress" class="audit-status"></div>
    </section>

    <div id="auditOutput"></div>
</div>

<script>
(() => {
'use strict';

const runButton=document.getElementById('runSiteAudit');
const progress=document.getElementById('auditProgress');
const output=document.getElementById('auditOutput');
const ruleUrl=@json(route('admin.adsense-audit.run'));
const aiUrl=@json(route('admin.articles.adsense-review'));
const csrf=@json(csrf_token());

const esc=v=>String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
const levelText=l=>l==='pass'?'PASS':l==='block'?'BLOCK':l==='warn'?'REVIEW':'INFO';
function badge(s){s=String(s||'').toUpperCase();const c=s==='READY'?'ready':s==='BLOCK'?'block':'review';return `<span class="audit-badge ${c}">${esc(s)}</span>`;}
function ruleCheck(c){return `<div class="audit-check"><span class="audit-level audit-level-${esc(c.level)}">${levelText(c.level)}</span><div><strong>${esc(c.title)}</strong><p>${esc(c.detail)}</p></div></div>`;}
function articleCheck(c){return `<div class="audit-article-check"><b>${levelText(c.level)} · ${esc(c.title)}</b><br>${esc(c.detail)}</div>`;}
function finalStatus(article,ai){
    if(article.block_count>0)return'BLOCK';
    const o=String(ai?.overall||'').toUpperCase();
    if(o==='HIGH_RISK')return'BLOCK';
    if(article.warning_count>0||o==='NEED_REVIEW')return'NEED_REVIEW';
    return'READY';
}
function renderBase(data){
    const articles=Array.isArray(data.articles)?data.articles:[];
    const rows=articles.map(a=>`
        <tr data-article-id="${a.id}">
            <td class="article-status">${badge(a.status)}</td>
            <td style="min-width:300px">
                <strong>${esc(a.title)}</strong>
                <div style="margin-top:4px;color:#64748b;font-size:10px">ID ${a.id}${a.category?' · '+esc(a.category):''}</div>
                <div style="margin-top:5px"><a href="${esc(a.url)}" target="_blank" rel="noopener">Mở bài ↗</a></div>
                ${a.ai_review_needed
                    ? `<div class="audit-ai" data-ai-box="${a.id}">Gemini: đang chờ AI review…</div>`
                    : `<div class="audit-ai pass">Gemini: rule-based scan không yêu cầu AI review.</div>`}
                <details class="audit-details"><summary>Xem rule-based checks</summary><div class="audit-article-checks">${a.checks.map(articleCheck).join('')}</div></details>
            </td>
            <td>${a.words}</td><td>${a.youtube_count}</td><td>${a.body_image_count}</td>
            <td>${a.block_count?`<div style="color:#991b1b">${a.block_count} block</div>`:''}${a.warning_count?`<div style="color:#854d0e">${a.warning_count} review</div>`:''}${!a.block_count&&!a.warning_count?'<span style="color:#166534">Không có</span>':''}</td>
        </tr>`).join('');

    output.innerHTML=`
        <div class="audit-summary">
            <div class="audit-stat">Published<b>${data.published_count}</b></div>
            <div class="audit-stat">AI candidates<b>${data.ai_candidate_count}</b></div>
            <div class="audit-stat">Site BLOCK<b>${data.site_blocks}</b></div>
            <div class="audit-stat">Site REVIEW<b>${data.site_warnings}</b></div>
        </div>
        <section class="audit-card"><h2>Rule-based Site Check</h2><div class="audit-checks">${(Array.isArray(data.site_checks)?data.site_checks:[]).map(ruleCheck).join('')}</div></section>
        <section class="audit-card"><h2>Published Articles</h2><div class="audit-table-wrap"><table class="audit-table">
            <thead><tr><th>Final</th><th>Article</th><th>Words</th><th>YouTube</th><th>Images</th><th>Rule issues</th></tr></thead>
            <tbody>${rows||'<tr><td colspan="6">Chưa có Published Article.</td></tr>'}</tbody>
        </table></div></section>
        <section class="audit-card" id="finalSummary"><h2>Kết quả cuối</h2><div id="finalSummaryBody">Đang chờ Gemini hoàn tất…</div></section>`;
}
async function runAi(c){
    const r=await fetch(aiUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({title:c.title,body:c.body,source_text:'',youtube_count:c.youtube_count,image_count:c.image_count})});
    let d={};try{d=await r.json()}catch(e){}
    if(!r.ok)throw new Error(d.message||'Gemini review failed.');
    return d;
}
function renderAi(id,r){
    const b=document.querySelector(`[data-ai-box="${id}"]`);if(!b)return;
    const o=String(r.overall||'NEED_REVIEW').toUpperCase();
    b.className='audit-ai '+(o==='PASS'?'pass':o==='HIGH_RISK'?'risk':'review');
    b.innerHTML=`<b>Gemini: ${esc(o)}</b><br>${esc(r.original_value||'')}${r.required_fixes?.length?'<br><b>Cần xử lý:</b> '+esc(r.required_fixes.join(' · ')):''}`;
}
function renderFinal(data,aiResults){
    let ready=0,review=0,block=0;
    (Array.isArray(data.articles)?data.articles:[]).forEach(a=>{
        const s=finalStatus(a,aiResults[a.id]||null);
        if(s==='READY')ready++;else if(s==='BLOCK')block++;else review++;
        const row=document.querySelector(`tr[data-article-id="${a.id}"]`);
        if(row)row.querySelector('.article-status').innerHTML=badge(s);
    });
    const final=(data.site_blocks>0||block>0)?'BLOCK':(data.site_warnings>0||review>0)?'NEED_REVIEW':'READY';
    const manual=(Array.isArray(data.manual_checks)?data.manual_checks:[]).map(x=>`<li>${esc(x)}</li>`).join('');
    const policy=(Array.isArray(data.policy_links)?data.policy_links:[]).map(x=>`<a href="${esc(x.url)}" target="_blank" rel="noopener" style="display:inline-flex;margin:5px 7px 0 0;padding:6px 8px;border:1px solid #dbe3ec;border-radius:7px;color:#2563eb;text-decoration:none;font-size:10px">${esc(x.label)} ↗</a>`).join('');
    document.getElementById('finalSummaryBody').innerHTML=`<div style="margin-bottom:10px">${badge(final)}</div><div>READY: <b>${ready}</b> · NEED REVIEW: <b>${review}</b> · BLOCK: <b>${block}</b></div>${manual?`<div style="margin-top:16px"><b>Kiểm tra thủ công bắt buộc</b><ul style="margin:7px 0 0 18px">${manual}</ul></div>`:''}${policy?`<div style="margin-top:14px"><b>Google Policy References</b><div>${policy}</div></div>`:''}`;
}
runButton.addEventListener('click',async()=>{
    runButton.disabled=true;const old=runButton.textContent;runButton.textContent='Đang quét toàn site…';
    progress.classList.add('open');progress.textContent='Bước 1/3 — Rule-based scan toàn bộ Published Articles…';output.innerHTML='';
    try{
        const r=await fetch(ruleUrl,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:'{}'});
        let data={};try{data=await r.json()}catch(e){}
        if(!r.ok)throw new Error(data.message||`Site-wide rule scan failed (HTTP ${r.status}).`);
        renderBase(data);
        const aiResults={};
        const candidates=Array.isArray(data.ai_candidates)?data.ai_candidates:[];
        if(candidates.length){
            for(let i=0;i<candidates.length;i++){
                const c=candidates[i];
                progress.textContent=`Bước 2/3 — Gemini ${i+1}/${candidates.length}: ${c.title}`;
                try{const res=await runAi(c);aiResults[c.article_id]=res;renderAi(c.article_id,res);}
                catch(e){aiResults[c.article_id]={overall:'NEED_REVIEW',original_value:'AI review không hoàn tất: '+(e.message||'unknown error'),required_fixes:['Chạy lại AI review cho bài này.']};renderAi(c.article_id,aiResults[c.article_id]);}
            }
        }
        progress.textContent='Bước 3/3 — Tổng hợp READY / NEED REVIEW / BLOCK…';
        renderFinal(data,aiResults);
        progress.textContent='Hoàn tất Site-wide AdSense Audit.';
    }catch(e){progress.innerHTML='<b style="color:#991b1b">Audit failed:</b> '+esc(e.message||'Unknown error');}
    finally{runButton.disabled=false;runButton.textContent=old;}
});
})();
</script>
@endsection
