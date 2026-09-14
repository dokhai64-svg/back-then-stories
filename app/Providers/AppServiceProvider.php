<?php
namespace App\Providers;
use App\Models\AdSlot; use App\Services\SiteResolver; use Illuminate\Support\Facades\Schema; use Illuminate\Support\Facades\View; use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider {
 public function register():void{}
 public function boot():void{
   View::composer('*',function($view){
     $site=null;$slots=collect(); try{ if(Schema::hasTable('sites')){$site=app(SiteResolver::class)->current(); if($site && Schema::hasTable('ad_slots')) $slots=AdSlot::where('site_id',$site->id)->where('enabled',true)->get()->keyBy('key'); }}catch(\Throwable $e){}
     $view->with('currentSite',$site)->with('activeAdSlots',$slots);
   });
 }
}
