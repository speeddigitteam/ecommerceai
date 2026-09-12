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
    protected $fillable = ['channel', 'name', 'subject', 'body', 'is_important'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_important' => 'boolean'];
    }
}
