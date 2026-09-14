<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Site extends Model {
 protected $fillable=['name','domain','logo','tagline','contact_email','active','gam_enabled','gam_network_id','gtm_container_id','ga4_measurement_id','ads_txt'];
 protected $casts=['active'=>'boolean','gam_enabled'=>'boolean'];
 public function articles(){return $this->hasMany(Article::class);} public function adSlots(){return $this->hasMany(AdSlot::class);}
}
