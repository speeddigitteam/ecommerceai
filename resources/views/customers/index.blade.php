<x-admin-layout title="Customers">
    <div x-data="{ addCustomerOpen: @js($errors->any()), showPassword: false, deletingCustomer: null }" class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />

            <div class="mx-auto max-w-7xl p-5 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Accounts</p><h1 class="mt-1 text-2xl font-bold">Customer list</h1><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">All customers who can sign in to your website.</p></div>
                    <button type="button" @click="addCustomerOpen = true" class="ds-button-primary inline-flex items-center justify-center gap-2"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add new customer</button>
                </div>

                @if (session('status'))<div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>@endif
                @if (session('error'))<div class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">{{ session('error') }}</div>@endif
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-[#161f2e]">
                    <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                        <h2 class="font-bold">All customers</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $customers->total() }} registered {{ Str::plural('customer', $customers->total()) }}</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Customer</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Joined</th><th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($customers as $customer)
                                    <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                @if ($customer->profile_image_path)
                                                    <img src="{{ asset('storage/'.$customer->profile_image_path) }}" alt="{{ $customer->name }}" class="h-10 w-10 rounded-full object-cover">
                                                @else
                                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-indigo-100 font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
                                                @endif
                                                <div><span class="font-semibold">{{ $customer->name }}</span>@if($customer->wholesale_status === 'approved')<span class="ml-2 rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold uppercase text-violet-700">Wholesale</span>@endif @if($customer->business_name)<p class="mt-1 text-xs text-slate-400">{{ $customer->business_name }}</p>@endif</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $customer->email }}</td>
                                        <td class="px-6 py-4">
                                            @if ($customer->email_verified_at)
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Verified</span>
                                            @else
                                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">Pending</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $customer->created_at->format('M d, Y') }}</td><td class="px-6 py-4"><div class="flex justify-end gap-2"><a href="{{ route('customers.show',$customer) }}" title="View" class="grid h-8 w-8 place-items-center rounded-full bg-sky-50 text-sky-600">◉</a><a href="{{ route('customers.edit',$customer) }}" title="Edit" class="grid h-8 w-8 place-items-center rounded-full bg-amber-50 text-amber-600">✎</a>@if($customer->purchase_count)<button disabled title="Has purchase history" class="grid h-8 w-8 cursor-not-allowed place-items-center rounded-full bg-slate-100 text-slate-300">⌫</button>@else<button type="button" @click="deletingCustomer={id:@js($customer->id),name:@js($customer->name)}" title="Delete" class="grid h-8 w-8 place-items-center rounded-full bg-rose-50 text-rose-600">⌫</button>@endif</div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No customers found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($customers->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $customers->links() }}</div>
                    @endif
                </section>
            </div>
        </main>

        <div x-cloak x-show="addCustomerOpen" x-transition.opacity @keydown.escape.window="addCustomerOpen = false" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div role="dialog" aria-modal="true" aria-labelledby="add-customer-title" @click.outside="addCustomerOpen = false" class="ds-card max-h-[90vh] w-full max-w-lg overflow-y-auto shadow-2xl">
                <div class="flex items-center justify-between border-b border-indigo-100 bg-indigo-50 px-5 py-4 dark:border-indigo-500/20 dark:bg-indigo-500/10">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">New account</p><h2 id="add-customer-title" class="mt-1 text-xl font-bold">Add new customer</h2></div>
                    <button type="button" @click="addCustomerOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-white hover:text-slate-700 dark:hover:bg-slate-800" aria-label="Close"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12"/></svg></button>
                </div>
                <form method="POST" action="{{ route('customers.store') }}">@csrf
                    <div class="space-y-5 p-5 sm:p-7">
                        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">The customer can sign in immediately using the email and password you provide.</div>
                        <div><label for="customer-name" class="ds-field-label">Customer name</label><input id="customer-name" name="name" value="{{ old('name') }}" required autofocus class="ds-input ds-control" placeholder="Enter full name">@error('name')<p class="ds-error">{{ $message }}</p>@enderror</div>
                        <div><label for="customer-email" class="ds-field-label">Email address</label><input id="customer-email" name="email" type="email" value="{{ old('email') }}" required class="ds-input ds-control" placeholder="customer@example.com">@error('email')<p class="ds-error">{{ $message }}</p>@enderror</div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div><label for="customer-password" class="ds-field-label">Password</label><div class="relative"><input id="customer-password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password" class="ds-input ds-control pr-11"><button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-indigo-600" x-text="showPassword ? 'Hide' : 'Show'"></button></div>@error('password')<p class="ds-error">{{ $message }}</p>@enderror</div>
                            <div><label for="customer-password-confirmation" class="ds-field-label">Confirm password</label><input id="customer-password-confirmation" name="password_confirmation" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password" class="ds-input ds-control"></div>
                        </div>
                        <p class="text-xs leading-5 text-slate-500">Use at least 8 characters. Share the password securely; it cannot be viewed again after saving.</p>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-700"><button type="button" @click="addCustomerOpen = false" class="ds-button-secondary">Cancel</button><button class="ds-button-primary">Create customer</button></div>
                </form>
            </div>
        </div>
        <div x-cloak x-show="deletingCustomer" x-transition.opacity @keydown.escape.window="deletingCustomer=null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"><div @click.outside="deletingCustomer=null" class="ds-card w-full max-w-md overflow-hidden shadow-2xl"><form method="POST" :action="'{{ url('/customers') }}/'+deletingCustomer?.id">@csrf @method('DELETE')<div class="p-6 text-center"><div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-rose-50 text-2xl text-rose-600">⌫</div><h2 class="mt-4 text-xl font-bold">Delete customer?</h2><p class="mt-2 text-sm text-slate-500">Permanently delete <strong x-text="deletingCustomer?.name"></strong>?</p></div><div class="flex justify-center gap-3 border-t border-slate-200 px-6 py-4 dark:border-slate-700"><button type="button" @click="deletingCustomer=null" class="ds-button-secondary">Cancel</button><button class="ds-button-danger">Yes, delete</button></div></form></div></div>
    </div>
</x-admin-layout>
