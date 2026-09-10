<x-storefront-layout title="Checkout">
<div x-data="{ customerName: @js(old('customer_name', auth()->user()?->name)), customerPhone: @js(old('customer_phone')), shippingAddress: @js(old('shipping_address')), customerNote: @js(old('customer_note')), subtotal: {{ $subtotal }}, hasPhysicalItem: @js($hasPhysicalItem), shippingCharge: {{ $hasPhysicalItem ? ($deliveryRates[old('delivery_area', 'dhaka_city')] ?? $deliveryRates['dhaka_city']) : 0 }}, deliveryArea: '{{ old('delivery_area', 'dhaka_city') }}', deliveryLabel() { return this.hasPhysicalItem ? { dhaka_city: 'ঢাকা সিটির ভেতরে', dhaka_outside: 'ঢাকা সিটির বাইরে', outside_dhaka: 'ঢাকা জেলার বাইরে' }[this.deliveryArea] : 'Digital delivery'; }, step: {{ $errors->any() ? 2 : 1 }}, go(n){ this.step=n; window.scrollTo({top:0,behavior:'smooth'}) }, delivery(){ const ids=this.hasPhysicalItem?['customer_name','customer_phone','shipping_address']:['customer_name','customer_phone']; const f=ids.map(id=>document.getElementById(id)).find(el=>!el.checkValidity()); if(f){f.reportValidity();return} this.go(3) } }" @cart-updated.window="subtotal = $event.detail.subtotal" class="bg-[#f1f4f5]">
<div class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-[1400px] items-center gap-4 overflow-x-auto px-4 py-4 sm:px-6 lg:px-8">
<a href="{{ route('cart.index') }}" class="shrink-0 rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold">&larr; Back</a>
<ol class="mx-auto flex min-w-max items-center" aria-label="Checkout progress">@foreach(['Account','Delivery','Payment','Review'] as $i=>$label) @if($i)<li class="mx-2 h-px w-8 sm:mx-4 sm:w-14" :class="step>{{ $i }}?'bg-emerald-500':'bg-slate-300'"></li>@endif<li class="flex items-center gap-2"><span class="grid h-9 w-9 place-items-center rounded-full border text-sm font-semibold" :class="step>{{ $i+1 }}?'border-emerald-500 bg-emerald-500 text-white':(step==={{ $i+1 }}?'border-slate-800 bg-slate-800 text-white':'border-slate-300 text-slate-500')"><span x-show="step<={{ $i+1 }}">{{ $i+1 }}</span><span x-show="step>{{ $i+1 }}" x-cloak>&#10003;</span></span><span class="text-sm" :class="step==={{ $i+1 }}?'font-semibold text-slate-950':'text-slate-500'">{{ $label }}</span></li>@endforeach</ol>
</div></div>
<form method="POST" action="{{ route('checkout.store') }}" class="mx-auto grid max-w-[1400px] gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:px-8">@csrf<input type="hidden" name="payment_method" value="cash_on_delivery">
<main class="min-w-0 rounded-[22px] bg-white p-5 shadow-sm sm:p-7">
@if($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
<section x-show="step===1" x-cloak><h1 class="text-xl font-semibold">How Would You Like to Checkout?</h1><p class="mt-1 text-sm text-slate-500">Choose the option that works best for you.</p>
<div class="mt-6 space-y-4"><div class="flex items-center gap-4 rounded-2xl border p-4 {{ auth()->check() ? 'border-slate-200' : 'border-sky-500 bg-sky-50/60 ring-1 ring-sky-500' }}"><span class="grid h-11 w-11 place-items-center rounded-full bg-slate-800 text-white">&#9675;</span><span class="flex-1"><strong class="block text-sm">Continue as Guest @guest<small class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] text-white">Recommended</small>@endguest</strong><small class="mt-1 block text-slate-500">Checkout without an account. Create one later.</small></span><span class="h-5 w-5 rounded-full {{ auth()->check() ? 'border border-slate-300' : 'border-[5px] border-sky-500' }}"></span></div>
@auth<div class="flex items-center gap-4 rounded-2xl border border-sky-500 bg-sky-50/60 p-4 ring-1 ring-sky-500"><span class="grid h-11 w-11 place-items-center rounded-full bg-slate-800 text-white">&#9675;</span><span class="min-w-0 flex-1"><strong class="block text-sm">Continue with Your Account <small class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] text-white">Recommended</small></strong><small class="block truncate text-slate-500">{{ auth()->user()->email }}</small></span><span class="h-5 w-5 rounded-full border-[5px] border-sky-500"></span></div>@else<a href="{{ route('login') }}" class="flex items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:border-sky-400"><span class="grid h-11 w-11 place-items-center rounded-full bg-slate-800 text-white">&#9675;</span><span class="flex-1"><strong class="block text-sm">Login to Your Account</strong><small class="text-slate-500">Access saved details and order history.</small></span><span>&rarr;</span></a>@endauth</div>
<div class="mt-6 grid gap-3 sm:grid-cols-2"><a href="{{ route('storefront.index') }}" class="flex h-11 items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold">Back to Home</a><button type="button" @click="go(2)" class="h-11 rounded-xl bg-slate-900 text-sm font-semibold text-white">Continue to Delivery</button></div></section>
<section x-show="step===2" x-cloak><h1 class="text-xl font-semibold">Delivery Info Details</h1><p class="mt-1 text-sm text-slate-500">Tell us where to deliver your order.</p><div class="mt-6 grid gap-5 sm:grid-cols-2">
<div><label for="customer_name" class="ds-field-label">Full name *</label><input id="customer_name" name="customer_name" x-model="customerName" value="{{ old('customer_name',auth()->user()?->name) }}" class="ds-input ds-control" required>@error('customer_name')<p class="ds-error">{{ $message }}</p>@enderror</div><div><label for="customer_phone" class="ds-field-label">Phone number *</label><input id="customer_phone" name="customer_phone" x-model="customerPhone" value="{{ old('customer_phone') }}" class="ds-input ds-control" required>@error('customer_phone')<p class="ds-error">{{ $message }}</p>@enderror</div><div class="sm:col-span-2"><label for="customer_email" class="ds-field-label">Email (optional)</label><input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email',auth()->user()?->email) }}" class="ds-input ds-control"></div><div x-show="hasPhysicalItem" class="sm:col-span-2"><label for="shipping_address" class="ds-field-label">Complete delivery address *</label><textarea id="shipping_address" name="shipping_address" x-model="shippingAddress" rows="4" class="ds-textarea ds-control" :required="hasPhysicalItem">{{ old('shipping_address') }}</textarea>@error('shipping_address')<p class="ds-error">{{ $message }}</p>@enderror</div><div class="sm:col-span-2"><label for="customer_note" class="ds-field-label">Order note (optional)</label><textarea id="customer_note" name="customer_note" x-model="customerNote" rows="3" class="ds-textarea ds-control">{{ old('customer_note') }}</textarea></div></div>
<div x-show="hasPhysicalItem" class="mt-7">
    <div class="flex items-end justify-between gap-4">
        <div><h2 class="text-lg font-semibold text-slate-950">Shipping Area</h2><p class="mt-1 text-xs text-slate-500">Select the area that matches your delivery address.</p></div>
        <span class="text-xs font-medium text-slate-400">Required</span>
    </div>
    <div class="mt-4 space-y-3">
        @foreach (App\Services\DeliveryCharges::AREAS as $area => $label)
        @php($charge = $deliveryRates[$area])
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3.5 transition" :class="deliveryArea === '{{ $area }}' ? 'border-sky-500 bg-sky-50/60 ring-1 ring-sky-500' : 'border-slate-200 hover:border-slate-300'">
                <input type="radio" name="delivery_area" value="{{ $area }}" x-model="deliveryArea" @change="shippingCharge = {{ $charge }}" class="border-slate-300 text-sky-600 focus:ring-sky-500" :required="hasPhysicalItem">
                <span class="flex-1 text-sm font-semibold text-slate-800">{{ $label }}</span>
                <strong class="text-sm text-slate-950"><span class="currency-symbol">&#2547;</span>{{ $charge }}</strong>
            </label>
        @endforeach
    </div>
    @error('delivery_area')<p class="ds-error">{{ $message }}</p>@enderror
</div><p x-show="! hasPhysicalItem" class="mt-7 rounded-xl bg-indigo-50 px-4 py-3 text-sm text-indigo-700">Your order is fully digital &mdash; no shipping needed. Download links unlock right after you place the order.</p><div class="mt-6 grid gap-3 sm:grid-cols-2"><button type="button" @click="go(1)" class="h-11 rounded-xl border text-sm font-semibold">Back to Account</button><button type="button" @click="delivery()" class="h-11 rounded-xl bg-slate-900 text-sm font-semibold text-white">Continue to Payment</button></div></section>
<section x-show="step===3" x-cloak><h1 class="text-xl font-semibold">Payment Method Options</h1><p class="mt-1 text-sm text-slate-500">Pick a payment option to continue to review.</p><div class="mt-6 space-y-4"><div class="flex items-center gap-4 rounded-2xl border border-sky-500 bg-sky-50/60 p-4 ring-1 ring-sky-500"><span class="grid h-11 w-11 place-items-center rounded-full bg-slate-800 text-white">&#9675;</span><span class="flex-1"><strong class="block text-sm">Cash on Delivery <small class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] text-white">Available</small></strong><small class="text-slate-500">Pay when you receive your order.</small></span><span class="h-5 w-5 rounded-full border-[5px] border-sky-500"></span></div>@foreach(['Credit / Debit Card','Mobile Banking'] as $method)<div class="flex items-center gap-4 rounded-2xl border bg-slate-50 p-4 opacity-60"><span class="grid h-11 w-11 place-items-center rounded-full bg-slate-700 text-white">&#9675;</span><span class="flex-1"><strong class="block text-sm">{{ $method }}</strong><small class="text-slate-500">Coming soon.</small></span><small class="rounded-full bg-slate-200 px-2 py-1">Unavailable</small></div>@endforeach</div><div class="mt-6 grid gap-3 sm:grid-cols-2"><button type="button" @click="go(2)" class="h-11 rounded-xl border text-sm font-semibold">Back to Delivery</button><button type="button" @click="go(4)" class="h-11 rounded-xl bg-slate-900 text-sm font-semibold text-white">Continue to Review</button></div></section>
<section x-show="step===4" x-cloak>
    <h1 class="text-xl font-semibold text-slate-950">Review Your Order</h1>
    <p class="mt-1 text-sm text-slate-500">Please review all details before placing your order.</p>

    <div class="mt-6 space-y-4">
        <div class="rounded-2xl border border-slate-200 p-4">
            <div class="flex items-start gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-slate-800 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h4l2 5-3 2a16 16 0 0 0 6 6l2-3 5 2v4a2 2 0 0 1-2 2C9 22 2 15 2 6a2 2 0 0 1 2-2Z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-3"><strong class="text-sm font-semibold">Phone Number</strong><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-400 text-[10px] text-slate-500">i</span></div>
                    <p class="mt-1 text-xs text-slate-500">Your contact number is required.</p>
                    <div class="mt-4 flex gap-3">
                        <span class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl border border-slate-200 px-3 text-xs font-medium"><span class="h-3 w-3 rounded-full bg-emerald-600 ring-1 ring-rose-500"></span>+880</span>
                        <div class="flex h-10 min-w-0 flex-1 items-center rounded-xl border border-slate-200 px-3 text-sm"><span class="min-w-0 flex-1 truncate" x-text="customerPhone"></span><button type="button" @click="go(2)" class="ml-3 text-slate-500 hover:text-slate-950" aria-label="Edit phone number">✎</button></div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="hasPhysicalItem" class="flex items-start gap-4 rounded-2xl border border-slate-200 p-4">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-slate-800 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
            <div class="min-w-0 flex-1">
                <strong class="block text-sm font-semibold">Delivery Address</strong>
                <p class="mt-1 whitespace-pre-line text-xs leading-5 text-slate-500" x-text="shippingAddress"></p>
                <div x-show="customerNote && customerNote.trim()" x-cloak class="mt-3 border-t border-slate-100 pt-3">
                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Order Note</span>
                    <p class="mt-1 whitespace-pre-line text-xs leading-5 text-slate-600" x-text="customerNote"></p>
                </div>
            </div>
            <button type="button" @click="go(2)" class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50" aria-label="Edit delivery address">✎</button>
        </div>

        <div class="flex items-start gap-4 rounded-2xl border border-slate-200 p-4">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-slate-800 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M8 3v4m8-4v4M3 10h18"/></svg></span>
            <div class="min-w-0 flex-1"><strong class="block text-sm font-semibold" x-text="hasPhysicalItem ? 'Delivery Schedule' : 'Delivery'"></strong><template x-if="hasPhysicalItem"><p class="mt-1 text-xs text-slate-500"><span x-text="deliveryLabel()"></span> &middot; <span class="currency-symbol">&#2547;</span><span x-text="shippingCharge"></span></p></template><template x-if="! hasPhysicalItem"><p class="mt-1 text-xs text-slate-500">Instant digital download &middot; no shipping charge</p></template></div>
            <button x-show="hasPhysicalItem" type="button" @click="go(2)" class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50" aria-label="Edit delivery schedule">✎</button>
        </div>

        <div class="rounded-2xl border border-slate-200 p-4">
            <div class="flex items-start gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-slate-800 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" stroke-width="1.8"/><path stroke-width="1.8" d="M3 10h18"/></svg></span>
                <div class="min-w-0 flex-1"><strong class="block text-sm font-semibold">Payment Method</strong><p class="mt-1 text-xs text-slate-500">Pay with cash when your order arrives.</p></div>
                <button type="button" @click="go(3)" class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50" aria-label="Edit payment method">✎</button>
            </div>
            <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs font-semibold tracking-wide text-slate-700">Cash on Delivery</div>
        </div>

        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-xs text-slate-600">
            <input type="checkbox" required class="rounded border-slate-300 text-emerald-500 focus:ring-emerald-500">
            <span>Placing this order means you agree to our Terms and Conditions.</span>
        </label>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <button type="button" @click="go(3)" class="h-11 rounded-xl border border-slate-200 text-sm font-semibold transition hover:bg-slate-50">Back to Payment</button>
        <button class="h-11 rounded-xl bg-slate-900 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800">Place Order &middot; <span class="currency-symbol">&#2547;</span><span x-text="Number(subtotal + shippingCharge).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ number_format($subtotal, 2) }}</span></button>
    </div>
</section>
</main>
<aside class="h-fit rounded-[26px] bg-white p-5 shadow-sm sm:p-7 lg:sticky lg:top-28">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-slate-950">Order Summary</h2>
        <span class="grid h-5 w-5 place-items-center rounded-full border border-slate-400 text-[11px] text-slate-500" title="Review the products in your cart">i</span>
    </div>

    <div class="mt-6 space-y-5">
        @foreach ($items as $item)
            <article x-data="{ quantity: {{ $item['quantity'] }}, busy: false, async changeQuantity(next) { if (this.busy || next < 0 || next > {{ $item['product']->stock_quantity }}) return; this.busy = true; try { const response = await fetch('{{ route('cart.update', $item['product']) }}', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams({ _token: '{{ csrf_token() }}', _method: 'PATCH', quantity: next }) }); if (!response.ok) return; const data = await response.json(); this.quantity = data.quantity; this.$dispatch('cart-updated', { subtotal: data.subtotal }); if (data.quantity === 0) { this.$el.remove(); if (data.cartCount === 0) window.location.href = '{{ route('cart.index') }}'; } } finally { this.busy = false; } } }" class="grid grid-cols-[76px_minmax(0,1fr)_auto] gap-3">
                <a href="{{ route('catalog.show', $item['product']->slug) }}" class="h-[76px] w-[76px] overflow-hidden rounded-xl bg-slate-100">
                    @if ($item['product']->featured_image_path)
                        <img src="{{ asset('storage/'.$item['product']->featured_image_path) }}" class="h-full w-full object-cover" alt="{{ $item['product']->title }}">
                    @endif
                </a>

                <div class="min-w-0">
                    @if ($item['product']->categories->isNotEmpty())
                        <p class="truncate text-xs italic text-sky-500">{{ $item['product']->categories->pluck('name')->join(', ') }}</p>
                    @endif
                    <h3 class="mt-0.5 truncate text-sm font-semibold text-slate-950">
                        <a href="{{ route('catalog.show', $item['product']->slug) }}">{{ $item['product']->title }}</a>
                    </h3>
                    <p class="mt-1 text-xs font-medium text-slate-600"><span class="currency-symbol">&#2547;</span>{{ number_format($item['unitPrice'], 2) }}@if($item['pricingType'] === 'wholesale') <span class="ml-1 rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold uppercase text-violet-700">Wholesale</span>@endif</p>
                </div>

                <div class="flex flex-col items-end justify-between">
                    <a href="{{ route('cart.index') }}" class="grid h-7 w-7 place-items-center rounded-full text-lg leading-none text-slate-800 transition hover:bg-rose-50 hover:text-rose-600" aria-label="Edit or remove {{ $item['product']->title }} from cart">&times;</a>
                    <div class="flex h-9 items-center overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" :class="busy && 'opacity-60'">
                        <button type="button" @click="changeQuantity(quantity - 1)" :disabled="busy" class="grid h-9 w-9 place-items-center text-lg text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Decrease {{ $item['product']->title }} quantity">&minus;</button>
                        <span class="grid h-9 min-w-7 place-items-center text-sm font-semibold tabular-nums" x-text="quantity">{{ $item['quantity'] }}</span>
                        <button type="button" @click="changeQuantity(quantity + 1)" :disabled="busy || quantity >= {{ $item['product']->stock_quantity }}" class="grid h-9 w-9 place-items-center text-lg text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Increase {{ $item['product']->title }} quantity">+</button>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-7">
        <h3 class="text-lg font-semibold text-slate-950">Promotion Code</h3>
        <div class="mt-3 flex h-12 items-center rounded-xl border border-slate-200 px-4 shadow-sm">
            <input type="text" disabled placeholder="Add Promo Code" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-xs shadow-none placeholder:text-slate-500 focus:ring-0">
            <span class="mx-3 h-6 w-px bg-slate-200"></span>
            <button type="button" disabled class="text-slate-800" aria-label="Apply promotion code">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="m20.5 11.1-14-7A2 2 0 0 0 3.7 6l1 4.1 8.3 1.9-8.3 1.9-1 4.1a2 2 0 0 0 2.8 2l14-7a1 1 0 0 0 0-1.8Z"/></svg>
            </button>
        </div>
        <p class="mt-2 text-xs text-slate-400">Promotion codes are coming soon.</p>
    </div>

    <div class="mt-7">
        <h3 class="text-lg font-semibold text-slate-950">Order Total</h3>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between text-slate-500"><dt>Subtotal</dt><dd class="font-semibold text-slate-900"><span class="currency-symbol">&#2547;</span><span x-text="Number(subtotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ number_format($subtotal, 2) }}</span></dd></div>
            <div class="flex items-center justify-between gap-4 text-slate-500"><dt class="min-w-0 truncate" x-text="deliveryLabel()">ঢাকা সিটির ভেতরে</dt><dd class="shrink-0 font-semibold text-slate-900"><span class="currency-symbol">&#2547;</span><span x-text="Number(shippingCharge).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ number_format($deliveryRates[old('delivery_area', 'dhaka_city')] ?? $deliveryRates['dhaka_city'], 2) }}</span></dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-4 text-base"><dt class="font-semibold text-slate-950">Total</dt><dd class="font-bold text-sky-500"><span class="currency-symbol">&#2547;</span><span x-text="Number(subtotal + shippingCharge).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ number_format($subtotal, 2) }}</span></dd></div>
        </dl>
    </div>
</aside>
</form></div>
</x-storefront-layout>
