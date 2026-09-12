<?php

namespace App\Models;

use Database\Factories\NoticeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    /** @use HasFactory<NoticeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['name', 'details', 'publish_to', 'is_active'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['publish_to' => 'array', 'is_active' => 'boolean'];
    }
}
