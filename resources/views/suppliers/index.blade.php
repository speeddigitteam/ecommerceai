<x-admin-layout title="Suppliers">
    <div
        x-data="{ supplierModalOpen: @js($errors->any()), editingSupplier: null, deletingSupplier: null }"
        class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100"
    >
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />

            <div class="mx-auto max-w-7xl p-5 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Purchasing</p>
                        <h1 class="mt-1 text-2xl font-bold">Supplier list</h1>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Manage supplier contact and business information.</p>
                    </div>
                    <button type="button" @click="editingSupplier = null; supplierModalOpen = true" class="ds-button-primary inline-flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                        Add new supplier
                    </button>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-[#161f2e]">
                    <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                        <h2 class="font-bold">All suppliers</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $suppliers->total() }} {{ Str::plural('supplier', $suppliers->total()) }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Supplier</th>
                                    <th class="px-6 py-4">Phone number</th>
                                    <th class="px-6 py-4">Business address</th>
                                    <th class="px-6 py-4">Added</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($suppliers as $supplier)
                                    <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ strtoupper(substr($supplier->name, 0, 1)) }}</div>
                                                <span class="font-semibold">{{ $supplier->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><a href="tel:{{ $supplier->phone }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ $supplier->phone }}</a></td>
                                        <td class="max-w-md px-6 py-4 text-slate-500 dark:text-slate-400">{{ $supplier->business_address }}</td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $supplier->created_at->format('M d, Y') }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="editingSupplier = @js($supplier->only(['id', 'name', 'phone', 'business_address'])); supplierModalOpen = true" title="Edit supplier" class="grid h-9 w-9 place-items-center rounded-full bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/15 dark:text-amber-300">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.9 3.1 4 4L7 21H3v-4L16.9 3.1Z"/></svg>
                                                </button>
                                                <button type="button" @click="deletingSupplier = @js($supplier->only(['id', 'name']))" title="Delete supplier" class="grid h-9 w-9 place-items-center rounded-full bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-300">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-14 text-center text-slate-500">No suppliers found. Add your first supplier to get started.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($suppliers->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $suppliers->links() }}</div>
                    @endif
                </section>
            </div>
        </main>

        <div x-cloak x-show="supplierModalOpen" x-transition.opacity @keydown.escape.window="supplierModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div role="dialog" aria-modal="true" aria-labelledby="supplier-modal-title" @click.outside="supplierModalOpen = false" class="ds-card max-h-[90vh] w-full max-w-lg overflow-y-auto shadow-2xl">
                <div class="flex items-center justify-between border-b border-indigo-100 bg-indigo-50 px-5 py-4 dark:border-indigo-500/20 dark:bg-indigo-500/10">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400" x-text="editingSupplier ? 'Update supplier' : 'New supplier'"></p>
                        <h2 id="supplier-modal-title" class="mt-1 text-xl font-bold" x-text="editingSupplier ? 'Edit supplier' : 'Add new supplier'"></h2>
                    </div>
                    <button type="button" @click="supplierModalOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-white hover:text-slate-700 dark:hover:bg-slate-800" aria-label="Close">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12"/></svg>
                    </button>
                </div>
                <form data-ds-dirty-submit method="POST" :action="editingSupplier ? '{{ url('/suppliers') }}/' + editingSupplier.id : '{{ route('suppliers.store') }}'">
                    @csrf
                    <template x-if="editingSupplier"><input type="hidden" name="_method" value="PUT"></template>
                    <div class="space-y-5 p-5 sm:p-7">
                        <div>
                            <label for="supplier-name" class="ds-field-label">Supplier name</label>
                            <input id="supplier-name" name="name" :value="editingSupplier?.name ?? @js(old('name'))" required class="ds-input ds-control" placeholder="Enter supplier or company name">
                            @error('name')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="supplier-phone" class="ds-field-label">Phone number</label>
                            <input id="supplier-phone" name="phone" type="tel" :value="editingSupplier?.phone ?? @js(old('phone'))" required class="ds-input ds-control" placeholder="01XXXXXXXXX">
                            @error('phone')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="supplier-address" class="ds-field-label">Business address</label>
                            <textarea id="supplier-address" name="business_address" rows="4" required class="ds-input ds-control resize-y" placeholder="Enter complete business address" x-text="editingSupplier?.business_address ?? @js(old('business_address'))"></textarea>
                            @error('business_address')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                        <button type="button" @click="supplierModalOpen = false" class="ds-button-secondary">Cancel</button>
                        <button class="ds-button-primary" x-text="editingSupplier ? 'Save changes' : 'Save supplier'"></button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak x-show="deletingSupplier" x-transition.opacity @keydown.escape.window="deletingSupplier = null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div @click.outside="deletingSupplier = null" class="ds-card w-full max-w-md overflow-hidden shadow-2xl">
                <form method="POST" :action="'{{ url('/suppliers') }}/' + deletingSupplier?.id">
                    @csrf
                    @method('DELETE')
                    <div class="p-6 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                        </div>
                        <h2 class="mt-4 text-xl font-bold">Delete supplier?</h2>
                        <p class="mt-2 text-sm text-slate-500">Permanently delete <strong x-text="deletingSupplier?.name"></strong>?</p>
                    </div>
                    <div class="flex justify-center gap-3 border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                        <button type="button" @click="deletingSupplier = null" class="ds-button-secondary">Cancel</button>
                        <button class="ds-button-danger">Yes, delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
