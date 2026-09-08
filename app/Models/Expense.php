<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['entry_date', 'remarks', 'total_amount'];

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['entry_date' => 'date', 'total_amount' => 'decimal:2'];
    }
}
