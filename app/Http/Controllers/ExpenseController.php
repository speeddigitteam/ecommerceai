<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountingEntryRequest;
use App\Http\Requests\UpdateAccountingEntryRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function store(StoreAccountingEntryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $validated = $request->validated();
            $items = $this->items($validated['items']);
            $expense = Expense::query()->create([
                'entry_date' => $validated['entry_date'],
                'remarks' => $validated['remarks'] ?? null,
                'total_amount' => collect($items)->sum('amount'),
            ]);
            $expense->items()->createMany($items);
        });

        return to_route('accounting.expenses')->with('status', 'Expense added successfully.');
    }

    public function update(UpdateAccountingEntryRequest $request, Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($request, $expense): void {
            $validated = $request->validated();
            $items = $this->items($validated['items']);
            $expense->update([
                'entry_date' => $validated['entry_date'],
                'remarks' => $validated['remarks'] ?? null,
                'total_amount' => collect($items)->sum('amount'),
            ]);
            $expense->items()->delete();
            $expense->items()->createMany($items);
        });

        return to_route('accounting.expenses')->with('status', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return to_route('accounting.expenses')->with('status', 'Expense deleted successfully.');
    }

    /**
     * @param  array<int, array{category_id: int|string, amount: int|float|string}>  $inputItems
     * @return array<int, array{expense_category_id: int, category_name: string, amount: float}>
     */
    private function items(array $inputItems): array
    {
        $categories = ExpenseCategory::query()->whereKey(collect($inputItems)->pluck('category_id'))->get()->keyBy('id');

        return collect($inputItems)->map(fn (array $item): array => [
            'expense_category_id' => (int) $item['category_id'],
            'category_name' => $categories->get((int) $item['category_id'])->name,
            'amount' => round((float) $item['amount'], 2),
        ])->all();
    }
}
