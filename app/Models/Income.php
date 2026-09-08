<?php

namespace App\Models;

use Database\Factories\IncomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Income extends Model
{
    /** @use HasFactory<IncomeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['entry_date', 'remarks', 'total_amount'];

    public function items(): HasMany
    {
        return $this->hasMany(IncomeItem::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['entry_date' => 'date', 'total_amount' => 'decimal:2'];
    }
}
