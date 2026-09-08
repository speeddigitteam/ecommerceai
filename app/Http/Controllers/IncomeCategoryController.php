<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeCategoryRequest;
use App\Http\Requests\UpdateIncomeCategoryRequest;
use App\Models\IncomeCategory;
use Illuminate\Http\RedirectResponse;

class IncomeCategoryController extends Controller
{
    public function store(StoreIncomeCategoryRequest $request): RedirectResponse
    {
        IncomeCategory::query()->create($request->validated());

        return to_route('accounting.income-categories')->with('status', 'Income category added successfully.');
    }

    public function update(UpdateIncomeCategoryRequest $request, IncomeCategory $incomeCategory): RedirectResponse
    {
        $incomeCategory->update($request->validated());

        return to_route('accounting.income-categories')->with('status', 'Income category updated successfully.');
    }

    public function destroy(IncomeCategory $incomeCategory): RedirectResponse
    {
        $incomeCategory->delete();

        return to_route('accounting.income-categories')->with('status', 'Income category deleted successfully.');
    }
}
