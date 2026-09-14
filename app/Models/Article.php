<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class Article extends Model {
 protected $fillable=['site_id','user_id','artist_id','category_id','title','slug','excerpt','body','featured_image','youtube_url','seo_title','meta_description','facebook_hook','status','published_at','featured'];
 protected $casts=['published_at'=>'datetime','featured'=>'boolean'];
 public function site(){return $this->belongsTo(Site::class);} public function author(){return $this->belongsTo(User::class,'user_id');} public function artist(){return $this->belongsTo(Artist::class);} public function category(){return $this->belongsTo(Category::class);}
 public function scopeLive(Builder $q): Builder { return $q->whereIn('status',['published','scheduled'])->whereNotNull('published_at')->where('published_at','<=',now()); }
}
