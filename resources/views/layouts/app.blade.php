<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', $currentSite?->name ?? 'Back Then Stories')</title><meta name="description" content="@yield('meta', $currentSite?->tagline ?? 'Classic entertainment stories for people who remember.')"><link rel="canonical" href="@yield('canonical', url()->current())">
<meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title', $currentSite?->name ?? 'Back Then Stories')))"/><meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('meta', $currentSite?->tagline ?? 'Classic entertainment stories')))"/><meta property="og:url" content="{{ url()->current() }}"/><meta property="og:type" content="@yield('og_type','website')"/>@hasSection('og_image')<meta property="og:image" content="@yield('og_image')"/>@endif
@if($currentSite?->gtm_container_id)<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $currentSite->gtm_container_id }}');</script>@endif

<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-G24CR8594J"></script>
<script>
window.dataLayer=window.dataLayer||[];
function gtag(){dataLayer.push(arguments);}
gtag('js',new Date());
gtag('config','G-G24CR8594J');
</script>

<style>:root{--ink:#171717;--muted:#666;--line:#e7e1d8;--paper:#fbfaf7;--accent:#7a2f22}*{box-sizing:border-box}body{margin:0;color:var(--ink);background:var(--paper);font-family:Arial,Helvetica,sans-serif}.wrap{max-width:1120px;margin:auto;padding:0 18px}.top{background:#171717;color:white;font-size:13px;padding:8px 0}.brand{padding:26px 0;border-bottom:1px solid var(--line)}.brand h1{margin:0;font-family:Georgia,serif;letter-spacing:.04em}.brand p{margin:4px 0 0;color:var(--muted)}nav{border-bottom:1px solid var(--line);overflow:auto;white-space:nowrap}nav a{display:inline-block;padding:14px 14px 14px 0;color:inherit;text-decoration:none;font-weight:700;font-size:13px}.hero{padding:38px 0}.grid{display:grid;grid-template-columns:2fr 1fr;gap:28px}.card{background:white;border:1px solid var(--line);border-radius:10px;overflow:hidden}.media{aspect-ratio:16/9;background:#d9d2c8;object-fit:cover;width:100%}.pad{padding:20px}.kicker{font-size:12px;text-transform:uppercase;font-weight:800;color:var(--accent);letter-spacing:.08em}h1,h2,h3{font-family:Georgia,serif}.deck{font-size:19px;line-height:1.55;color:#343434}.meta{color:var(--muted);font-size:13px}.article{max-width:760px;margin:0 auto;padding:36px 18px}.article .body{font-family:Georgia,serif;font-size:20px;line-height:1.8}.article .body img{max-width:100%;height:auto}.ad{border:1px dashed #aaa;background:#f2f2f2;text-align:center;padding:18px 10px;margin:28px 0;color:#777;font:12px Arial;min-height:80px;display:flex;align-items:center;justify-content:center}.story{padding:18px 0;border-top:1px solid var(--line)}footer{margin-top:50px;background:#171717;color:#ddd;padding:30px 0;font-size:13px}a{color:#6b2a20}@media(max-width:760px){.grid{grid-template-columns:1fr}.brand{padding:18px 0}.article{padding-top:24px}.article .body{font-size:18px}.article h1{font-size:36px!important}}</style>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5430864416594585"
     crossorigin="anonymous"></script>

@stack('head')</head><body>@if($currentSite?->gtm_container_id)<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $currentSite->gtm_container_id }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>@endif
<div class="top"><div class="wrap">{{ $currentSite?->tagline ?? 'Classic entertainment stories for people who remember' }}</div></div><header class="brand"><div class="wrap"><h1>{{ strtoupper($currentSite?->name ?? 'BACK THEN STORIES') }}</h1><p>{{ $currentSite?->tagline ?? 'The music, stars & moments we never forgot' }}</p></div></header><nav><div class="wrap"><a href="{{ route('home') }}">HOME</a><a href="{{ route('about') }}">ABOUT</a><a href="{{ route('contact') }}">CONTACT</a></div></nav>
@yield('content')<footer><div class="wrap">© {{ date('Y') }} {{ $currentSite?->name ?? 'Back Then Stories' }} · <a style="color:inherit" href="{{ route('privacy') }}">Privacy</a> · <a style="color:inherit" href="{{ route('terms') }}">Terms</a> · <a style="color:inherit" href="{{ route('editorial') }}">Editorial Policy</a></div></footer>@if($currentSite?->gam_enabled && $activeAdSlots->where('type','display')->count())<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script><script>window.googletag=window.googletag||{cmd:[]};window.addEventListener('DOMContentLoaded',()=>{const els=[...document.querySelectorAll('[data-gam-slot]')];if(!els.length)return;googletag.cmd.push(()=>{els.forEach(el=>{let sizes=[];try{sizes=JSON.parse(el.dataset.sizes||'[]').map(s=>String(s).split('x').map(Number)).filter(a=>a.length===2&&a.every(Number.isFinite))}catch(e){}if(!sizes.length)sizes=[[300,250]];googletag.defineSlot(el.dataset.adUnit,sizes,el.id)?.addService(googletag.pubads())});googletag.pubads().enableSingleRequest();googletag.pubads().collapseEmptyDivs();googletag.enableServices();els.forEach(el=>googletag.display(el.id));});});</script>
@endif

@stack('scripts')

</body>
</html>
