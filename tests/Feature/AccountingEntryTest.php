<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_multi_category_expense(): void
    {
        $admin = User::factory()->create();
        $first = ExpenseCategory::factory()->create();
        $second = ExpenseCategory::factory()->create();
        $this->actingAs($admin)->post(route('expenses.store'), [
            'entry_date' => '2026-08-29', 'remarks' => 'Costs',
            'items' => [
                ['category_id' => $first->id, 'amount' => '1000.50'],
                ['category_id' => $second->id, 'amount' => '249.50'],
            ],
        ])->assertRedirect(route('accounting.expenses'));
        $expense = Expense::query()->firstOrFail();
        $this->assertSame('1250.00', $expense->total_amount);
        $this->assertCount(2, $expense->items);
        $this->actingAs($admin)->put(route('expenses.update', $expense), [
            'entry_date' => '2026-08-30', 'remarks' => null,
            'items' => [['category_id' => $first->id, 'amount' => '900']],
        ])->assertRedirect(route('accounting.expenses'));
        $this->assertSame('900.00', $expense->fresh()->total_amount);
        $this->assertCount(1, $expense->fresh()->items);
        $this->actingAs($admin)->delete(route('expenses.destroy', $expense))->assertRedirect(route('accounting.expenses'));
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_admin_can_manage_multi_category_income(): void
    {
        $admin = User::factory()->create();
        $first = IncomeCategory::factory()->create();
        $second = IncomeCategory::factory()->create();
        $this->actingAs($admin)->post(route('incomes.store'), [
            'entry_date' => '2026-08-29',
            'items' => [
                ['category_id' => $first->id, 'amount' => '500'],
                ['category_id' => $second->id, 'amount' => '250'],
            ],
        ])->assertRedirect(route('accounting.income'));
        $income = Income::query()->firstOrFail();
        $this->assertSame('750.00', $income->total_amount);
        $this->assertCount(2, $income->items);
        $this->actingAs($admin)->put(route('incomes.update', $income), [
            'entry_date' => '2026-08-30',
            'items' => [['category_id' => $first->id, 'amount' => '600']],
        ])->assertRedirect(route('accounting.income'));
        $this->assertSame('600.00', $income->fresh()->total_amount);
        $this->actingAs($admin)->delete(route('incomes.destroy', $income))->assertRedirect(route('accounting.income'));
        $this->assertDatabaseMissing('incomes', ['id' => $income->id]);
    }
}
