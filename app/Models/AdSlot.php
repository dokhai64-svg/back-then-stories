<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class AdSlot extends Model { protected $fillable=['site_id','key','label','ad_unit_path','sizes','enabled','type']; protected $casts=['sizes'=>'array','enabled'=>'boolean']; public function site(){return $this->belongsTo(Site::class);} }
