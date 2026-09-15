<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleChapter extends Model
{
    protected $fillable = [
        'article_id',
        'chapter_number',
        'title',
        'body',
        'views',
    ];

    protected $casts = [
        'chapter_number' => 'integer',
        'views' => 'integer',
    ];

    public function article()
    {
        return $this->belongsTo(
            Article::class
        );
    }
}
