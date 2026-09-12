<?php

namespace App\Models;

use Database\Factories\CommunicationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    /** @use HasFactory<CommunicationLogFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['communication_provider_id', 'channel', 'recipient', 'subject', 'body', 'status', 'provider_response', 'error_message', 'sent_at'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(CommunicationProvider::class, 'communication_provider_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
