<?php

namespace App\Models;

use Database\Factories\BlogPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogPost extends Model
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['author_id', 'title', 'slug', 'excerpt', 'content', 'status', 'visibility', 'published_at', 'featured_image_path', 'gallery_paths', 'tags', 'focus_keyword', 'seo_title', 'meta_description'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['gallery_paths' => 'array', 'tags' => 'array', 'published_at' => 'datetime'];
    }
}
