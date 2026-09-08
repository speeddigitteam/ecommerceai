<?php

namespace App\Models;

use Database\Factories\CourierIntegrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierIntegration extends Model
{
    /** @use HasFactory<CourierIntegrationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'provider',
        'name',
        'api_key',
        'secret_key',
        'base_url',
        'is_active',
        'last_tested_at',
        'last_test_succeeded',
        'last_error',
    ];

    /** @var list<string> */
    protected $hidden = ['api_key', 'secret_key'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
            'last_test_succeeded' => 'boolean',
        ];
    }
}
