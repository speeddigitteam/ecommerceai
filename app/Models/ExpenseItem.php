<?php

namespace App\Models;

use Database\Factories\ExpenseItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    /** @use HasFactory<ExpenseItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['expense_category_id', 'category_name', 'amount'];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
