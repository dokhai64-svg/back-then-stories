<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Media extends Model { protected $fillable=['user_id','disk','path','filename','mime_type','size','alt_text']; public function user(){return $this->belongsTo(User::class);} }
