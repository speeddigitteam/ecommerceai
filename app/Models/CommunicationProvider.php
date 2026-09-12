<?php

namespace App\Models;

use Database\Factories\CommunicationProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationProvider extends Model
{
    /** @use HasFactory<CommunicationProviderFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['channel', 'name', 'driver', 'settings', 'is_active'];

    /** @var list<string> */
    protected $hidden = ['settings'];

    public function logs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['settings' => 'encrypted:array', 'is_active' => 'boolean'];
    }
}
