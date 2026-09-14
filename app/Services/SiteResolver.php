<?php
namespace App\Services; use App\Models\Site; use Illuminate\Http\Request;
class SiteResolver { public function __construct(private Request $request){} public function current():?Site{ $host=$this->request->getHost(); return Site::query()->where('active',true)->where('domain',$host)->first() ?? Site::query()->where('active',true)->oldest()->first(); } }
