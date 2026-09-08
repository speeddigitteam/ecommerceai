<?php

namespace App\Models;

use Database\Factories\IncomeItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeItem extends Model
{
    /** @use HasFactory<IncomeItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['income_category_id', 'category_name', 'amount'];

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
