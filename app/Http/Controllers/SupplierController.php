<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('suppliers.index', ['suppliers' => Supplier::query()->latest()->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request): RedirectResponse
    {
        Supplier::query()->create($this->validatedSupplier($request));

        return to_route('suppliers.index')->with('status', 'Supplier created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validatedSupplier($request, $supplier));

        return to_route('suppliers.index')->with('status', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return to_route('suppliers.index')->with('status', 'Supplier deleted successfully.');
    }

    /** @return array{name: string, phone: string, business_address: string} */
    private function validatedSupplier(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('suppliers', 'phone')->ignore($supplier)],
            'business_address' => ['required', 'string', 'max:1000'],
        ]);
    }
}
