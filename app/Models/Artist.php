<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Artist extends Model { protected $fillable=['name','slug','bio','image']; public function articles(){return $this->hasMany(Article::class);} }
