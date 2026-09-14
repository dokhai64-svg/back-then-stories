<?php
namespace App\Http\Controllers\Admin; use App\Http\Controllers\Controller; use App\Models\{Article,Artist,Category,Media,Site};
class DashboardController extends Controller { public function __invoke(){ $stats=['articles'=>Article::count(),'published'=>Article::where('status','published')->count(),'artists'=>Artist::count(),'categories'=>Category::count(),'media'=>Media::count(),'sites'=>Site::count()];$recent=Article::with(['site','author'])->latest()->take(8)->get();return view('admin.dashboard',compact('stats','recent')); } }
