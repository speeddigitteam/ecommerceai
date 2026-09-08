<x-storefront-layout title="Shopping cart">
    <div class="bg-[#f1f4f5] px-4 py-8 sm:px-6 sm:py-12 lg:px-8 lg:py-16">
        <div class="mx-auto max-w-lg">
            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>
            @endif

            @if ($items->isEmpty())
                <div class="rounded-[26px] bg-white p-10 text-center shadow-sm sm:p-14">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-slate-100 text-slate-600"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l2.2 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L21 7H6m4 13a1.25 1.25 0 1 1-2.5 0 1.25 1.25 0 0 1 2.5 0Zm8 0a1.25 1.25 0 1 1-2.5 0 1.25 1.25 0 0 1 2.5 0Z"/></svg></span>
                    <h1 class="mt-5 text-2xl font-semibold">Your cart is empty</h1>
                    <p class="mt-2 text-sm text-slate-500">Find something you love and add it here.</p>
                    <a href="{{ route('storefront.index') }}" class="mt-6 inline-flex rounded-xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white">Continue shopping</a>
                </div>
            @else
                <section x-data="{ subtotal: {{ $subtotal }} }" @cart-updated.window="subtotal = $event.detail.subtotal" class="rounded-[26px] bg-white p-5 shadow-sm sm:p-7">
                    <div class="flex items-center justify-between">
                        <h1 class="text-xl font-semibold text-slate-950">Order Summary</h1>
                        <span class="grid h-5 w-5 place-items-center rounded-full border border-slate-400 text-[11px] text-slate-500" title="Review the products in your cart">i</span>
                    </div>

                    <div class="mt-6 space-y-5">
                        @foreach ($items as $item)
                            <article x-data="{ quantity: {{ $item['quantity'] }}, busy: false, async changeQuantity(next) { if (this.busy || next < 0 || next > {{ $item['product']->stock_quantity }}) return; this.busy = true; try { const response = await fetch('{{ route('cart.update', $item['product']) }}', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams({ _token: '{{ csrf_token() }}', _method: 'PATCH', quantity: next }) }); if (!response.ok) return; const data = await response.json(); this.quantity = data.quantity; this.$dispatch('cart-updated', { subtotal: data.subtotal }); if (data.quantity === 0) { this.$el.remove(); if (data.cartCount === 0) window.location.reload(); } } finally { this.busy = false; } } }" class="grid grid-cols-[76px_minmax(0,1fr)_auto] gap-3">
                                <a href="{{ route('catalog.show', $item['product']->slug) }}" class="h-[76px] w-[76px] overflow-hidden rounded-xl bg-slate-100">
                                    @if ($item['product']->featured_image_path)
                                        <img src="{{ asset('storage/'.$item['product']->featured_image_path) }}" class="h-full w-full object-cover" alt="{{ $item['product']->title }}">
                                    @endif
                                </a>

                                <div class="min-w-0">
                                    @if ($item['product']->categories->isNotEmpty())
                                        <p class="truncate text-xs italic text-sky-500">{{ $item['product']->categories->pluck('name')->join(', ') }}</p>
                                    @endif
                                    <h2 class="mt-0.5 truncate text-sm font-semibold text-slate-950">
                                        <a href="{{ route('catalog.show', $item['product']->slug) }}">{{ $item['product']->title }}</a>
                                    </h2>
                                    <p class="mt-1 text-xs font-medium text-slate-600"><span class="currency-symbol">&#2547;</span>{{ number_format($item['product']->current_price, 2) }}</p>
                                </div>

                                <div class="flex flex-col items-end justify-between">
                                    <form method="POST" action="{{ route('cart.destroy', $item['product']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="grid h-7 w-7 place-items-center rounded-full text-lg leading-none text-slate-800 transition hover:bg-rose-50 hover:text-rose-600" aria-label="Remove {{ $item['product']->title }}">&times;</button>
                                    </form>

                                    <div class="flex h-9 items-center overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" :class="busy && 'opacity-60'">
                                        <button type="button" @click="changeQuantity(quantity - 1)" :disabled="busy" class="grid h-9 w-9 place-items-center text-lg text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Decrease {{ $item['product']->title }} quantity">&minus;</button>
                                        <span class="grid h-9 min-w-7 place-items-center text-sm font-semibold tabular-nums" x-text="quantity"></span>
                                        <button type="button" @click="changeQuantity(quantity + 1)" :disabled="busy || quantity >= {{ $item['product']->stock_quantity }}" class="grid h-9 w-9 place-items-center text-lg text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Increase {{ $item['product']->title }} quantity">+</button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-7">
                        <h2 class="text-lg font-semibold text-slate-950">Promotion Code</h2>
                        <div class="mt-3 flex h-12 items-center rounded-xl border border-slate-200 px-4 shadow-sm">
                            <input type="text" disabled placeholder="Add Promo Code" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-xs shadow-none placeholder:text-slate-500 focus:ring-0">
                            <span class="mx-3 h-6 w-px bg-slate-200"></span>
                            <button type="button" disabled class="text-slate-800" aria-label="Apply promotion code">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="m20.5 11.1-14-7A2 2 0 0 0 3.7 6l1 4.1 8.3 1.9-8.3 1.9-1 4.1a2 2 0 0 0 2.8 2l14-7a1 1 0 0 0 0-1.8Z"/></svg>
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">Promotion codes are coming soon.</p>
                    </div>

                    <dl class="mt-7 space-y-3 border-t border-slate-200 pt-5 text-sm">
                        <div class="flex justify-between text-slate-500"><dt>Subtotal</dt><dd class="font-semibold text-slate-900"><span class="currency-symbol">&#2547;</span><span x-text="Number(subtotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ number_format($subtotal, 2) }}</span></dd></div>
                        <div class="flex justify-between text-slate-500"><dt>Delivery</dt><dd class="font-semibold text-slate-900">FREE</dd></div>
                        <div class="flex justify-between pt-2 text-base"><dt class="font-semibold">Total</dt><dd class="font-bold text-sky-500"><span class="currency-symbol">&#2547;</span><span x-text="Number(subtotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ number_format($subtotal, 2) }}</span></dd></div>
                    </dl>

                    <a href="{{ route('checkout.create') }}" class="mt-6 flex h-12 w-full items-center justify-center rounded-xl bg-slate-900 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800">Proceed to Checkout</a>
                    <a href="{{ route('storefront.index') }}" class="mt-3 flex h-10 items-center justify-center text-sm font-semibold text-slate-500 transition hover:text-slate-900">Continue Shopping</a>
                </section>
            @endif
        </div>
    </div>
</x-storefront-layout>
