<?php

namespace App\Models;

use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'path',
        'slug',
        'original_name',
        'mime_type',
        'size',
        'alt_text',
        'title',
        'caption',
        'description',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
