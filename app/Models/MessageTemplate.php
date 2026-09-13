<?php

namespace App\Models;

use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['channel', 'key', 'category', 'trigger', 'name', 'subject', 'preview_text', 'body', 'button_text', 'button_url', 'is_important', 'is_active'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_important' => 'boolean', 'is_active' => 'boolean'];
    }
}
