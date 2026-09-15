<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'site_id',
        'user_id',
        'artist_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'featured_image',
        'youtube_url',
        'seo_title',
        'meta_description',
        'facebook_hook',
        'status',
        'published_at',
        'featured',
        'views',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'featured' => 'boolean',
        'views' => 'integer',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function scopePublished($query)
    {
        return $query
            ->where(
                'status',
                'published'
            )
            ->where(function ($q) {
                $q->whereNull(
                    'published_at'
                )->orWhere(
                    'published_at',
                    '<=',
                    now()
                );
            });
    }

    /*
     * Backward-compatible alias.
     * Some existing public controllers still call Article::live().
     * Keep it equivalent to the published scope.
     */
    public function scopeLive($query)
    {
        return $this->scopePublished(
            $query
        );
    }
}
