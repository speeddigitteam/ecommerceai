<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        return view('units.index', ['units' => Unit::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:units,name']]);
        Unit::create($validated);

        return to_route('units.index')->with('status', 'Unit created successfully.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:units,name,'.$unit->id]]);
        $unit->update($validated);

        return to_route('units.index')->with('status', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return to_route('units.index')->with('status', 'Unit deleted successfully.');
    }
}
