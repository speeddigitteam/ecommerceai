<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\IncomeCategory;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function income(): View
    {
        return view('accounting.entries', [
            'type' => 'income',
            'title' => 'Income',
            'entries' => Income::query()->with('items')->latest('entry_date')->latest()->paginate(15),
            'categories' => IncomeCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function expenses(): View
    {
        return view('accounting.entries', [
            'type' => 'expense',
            'title' => 'Expense',
            'entries' => Expense::query()->with('items')->latest('entry_date')->latest()->paginate(15),
            'categories' => ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function incomeCategories(): View
    {
        return view('accounting.categories', [
            'type' => 'income',
            'title' => 'Income Categories',
            'categories' => IncomeCategory::query()->latest()->paginate(15),
        ]);
    }

    public function expenseCategories(): View
    {
        return view('accounting.categories', [
            'type' => 'expense',
            'title' => 'Expense Categories',
            'categories' => ExpenseCategory::query()->latest()->paginate(15),
        ]);
    }

    public function reports(): View
    {
        return $this->page('Reports', 'Review income, expenses and accounting summaries.');
    }

    private function page(string $title, string $description): View
    {
        return view('accounting.page', [
            'title' => $title,
            'description' => $description,
        ]);
    }
}
