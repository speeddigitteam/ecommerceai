<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountingEntryRequest;
use App\Http\Requests\UpdateAccountingEntryRequest;
use App\Models\Income;
use App\Models\IncomeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    public function store(StoreAccountingEntryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $validated = $request->validated();
            $items = $this->items($validated['items']);
            $income = Income::query()->create([
                'entry_date' => $validated['entry_date'],
                'remarks' => $validated['remarks'] ?? null,
                'total_amount' => collect($items)->sum('amount'),
            ]);
            $income->items()->createMany($items);
        });

        return to_route('accounting.income')->with('status', 'Income added successfully.');
    }

    public function update(UpdateAccountingEntryRequest $request, Income $income): RedirectResponse
    {
        DB::transaction(function () use ($request, $income): void {
            $validated = $request->validated();
            $items = $this->items($validated['items']);
            $income->update([
                'entry_date' => $validated['entry_date'],
                'remarks' => $validated['remarks'] ?? null,
                'total_amount' => collect($items)->sum('amount'),
            ]);
            $income->items()->delete();
            $income->items()->createMany($items);
        });

        return to_route('accounting.income')->with('status', 'Income updated successfully.');
    }

    public function destroy(Income $income): RedirectResponse
    {
        $income->delete();

        return to_route('accounting.income')->with('status', 'Income deleted successfully.');
    }

    /**
     * @param  array<int, array{category_id: int|string, amount: int|float|string}>  $inputItems
     * @return array<int, array{income_category_id: int, category_name: string, amount: float}>
     */
    private function items(array $inputItems): array
    {
        $categories = IncomeCategory::query()->whereKey(collect($inputItems)->pluck('category_id'))->get()->keyBy('id');

        return collect($inputItems)->map(fn (array $item): array => [
            'income_category_id' => (int) $item['category_id'],
            'category_name' => $categories->get((int) $item['category_id'])->name,
            'amount' => round((float) $item['amount'], 2),
        ])->all();
    }
}
