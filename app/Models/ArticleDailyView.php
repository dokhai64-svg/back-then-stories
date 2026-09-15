<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleDailyView extends Model
{
    protected $fillable = [
        'article_id',
        'site_id',
        'view_date',
        'views',
    ];

    protected $casts = [
        'view_date' => 'date',
        'views' => 'integer',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
