<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleAlias extends Model
{
    protected $fillable = [
        'article_id',
        'site_id',
        'slug',
    ];

    public function article()
    {
        return $this->belongsTo(
            Article::class
        );
    }

    public function site()
    {
        return $this->belongsTo(
            Site::class
        );
    }
}
