<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdSlotAudit extends Model
{
    protected $fillable = [
        'ad_slot_id',
        'site_id',
        'user_id',
        'action',
        'changed_fields',
    ];

    protected $casts = [
        'changed_fields' => 'array',
    ];

    public function adSlot()
    {
        return $this->belongsTo(AdSlot::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
