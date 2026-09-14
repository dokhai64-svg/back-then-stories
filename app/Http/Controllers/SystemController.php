<?php
namespace App\Http\Controllers; use App\Models\Article; use App\Services\SiteResolver;
class SystemController extends Controller {
 public function ads(SiteResolver $r){$s=$r->current(); return response($s?->ads_txt ?? '',200,['Content-Type'=>'text/plain; charset=utf-8']);}
 public function robots(){return response("User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')."\n",200,['Content-Type'=>'text/plain']);}
 public function sitemap(SiteResolver $r){$s=$r->current();$articles=$s?Article::live()->where('site_id',$s->id)->latest('updated_at')->get():collect();return response()->view('system.sitemap',compact('articles'))->header('Content-Type','application/xml');}
}
