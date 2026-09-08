<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Order;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('customers.index', [
            'customers' => User::query()
                ->where('role', UserRole::Customer)
                ->addSelect(['purchase_count' => Order::query()->selectRaw('count(*)')->whereColumn('orders.user_id', 'users.id')->orWhere(fn (Builder $query) => $query->whereNull('orders.user_id')->whereColumn('orders.customer_email', 'users.email'))])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $customer = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'role' => UserRole::Customer,
        ]);
        $customer->forceFill(['email_verified_at' => now()])->save();

        return to_route('customers.index')->with('status', "Customer {$customer->name} created successfully. You can now share the login credentials.");
    }

    public function show(User $customer): View
    {
        $this->ensureCustomer($customer);

        return view('customers.show', ['customer' => $customer, 'purchaseCount' => $this->purchaseQuery($customer)->count(), 'recentOrders' => $this->purchaseQuery($customer)->latest()->limit(5)->get()]);
    }

    public function edit(User $customer): View
    {
        $this->ensureCustomer($customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);
        $validated = $request->safe()->except('password');
        if ($request->filled('password')) {
            $validated['password'] = $request->validated('password');
        }
        $customer->update($validated);

        return to_route('customers.index')->with('status', 'Customer updated successfully.');
    }

    public function destroy(User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);
        if ($this->purchaseQuery($customer)->exists()) {
            return to_route('customers.index')->with('error', 'This customer has purchase history and cannot be deleted.');
        }
        if ($customer->profile_image_path) {
            Storage::disk('public')->delete($customer->profile_image_path);
        }
        $customer->delete();

        return to_route('customers.index')->with('status', 'Customer deleted successfully.');
    }

    private function ensureCustomer(User $customer): void
    {
        abort_unless($customer->role === UserRole::Customer, 404);
    }

    private function purchaseQuery(User $customer): Builder
    {
        return Order::query()->where(fn (Builder $query) => $query->where('user_id', $customer->id)->orWhere(fn (Builder $legacyQuery) => $legacyQuery->whereNull('user_id')->where('customer_email', $customer->email)));
    }
}
