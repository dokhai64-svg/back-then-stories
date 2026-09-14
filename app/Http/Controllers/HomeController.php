<?php
namespace App\Http\Controllers; use App\Models\Article; use App\Services\SiteResolver;
class HomeController extends Controller { public function index(SiteResolver $resolver){$site=$resolver->current(); if(!$site) return view('home.empty'); $base=Article::live()->where('site_id',$site->id); $featured=(clone $base)->where('featured',true)->latest('published_at')->first() ?? (clone $base)->latest('published_at')->first(); $latest=(clone $base)->with(['category','artist'])->latest('published_at')->take(12)->get(); return view('home.index',compact('site','featured','latest'));} }
