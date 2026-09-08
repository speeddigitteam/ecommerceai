<x-storefront-layout title="Track your order" description="Check the latest status of your order.">
    @php
        $steps = ['pending', 'confirmed', 'processing', 'ready_to_ship', 'shipped', 'completed'];
        $currentStep = isset($order) ? array_search($order->status, $steps, true) : false;
        $isExceptional = isset($order) && in_array($order->status, ['cancelled', 'returned'], true);
    @endphp

    <section class="bg-slate-50 px-4 py-12 text-slate-900 sm:px-6 sm:py-16 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <span class="inline-flex rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-xs font-bold uppercase tracking-[.16em] text-indigo-600">Order tracking</span>
            <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-5xl">Where is your order?</h1>
            <p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-slate-600 sm:text-base">Enter the order number and phone number used during checkout to see the latest delivery progress.</p>
            <form method="POST" action="{{ route('storefront.orders.track.show') }}" class="mt-8 grid gap-3 rounded-2xl bg-white p-3 text-left shadow-2xl sm:grid-cols-[1fr_1fr_auto]">
                @csrf
                <label class="sr-only" for="order_number">Order number</label>
                <input id="order_number" name="order_number" value="{{ old('order_number') }}" placeholder="Order ID e.g. ORD00008" autocomplete="off" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:font-normal focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100">
                <label class="sr-only" for="customer_phone">Phone number</label>
                <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="Checkout phone number" autocomplete="tel" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:font-normal focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100">
                <button class="h-12 rounded-xl bg-indigo-600 px-6 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">Track order</button>
            </form>
            @if ($errors->any())<p class="mt-3 text-sm font-semibold text-rose-600">{{ $errors->first() }}</p>@endif
        </div>
    </section>

    <section class="bg-slate-50 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-6xl">
            @if (($searched ?? false) && ! isset($order))
                <div class="mx-auto max-w-2xl rounded-3xl border border-rose-200 bg-white px-6 py-12 text-center shadow-sm">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-rose-50 text-rose-600"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m20 20-4-4"/></svg></span>
                    <h2 class="mt-5 text-xl font-extrabold text-slate-900">Order not found</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">The order number and phone number did not match. Check both details and try again.</p>
                </div>
            @elseif (isset($order))
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-5 border-b border-slate-200 bg-slate-900 px-6 py-6 text-white sm:flex-row sm:items-center sm:justify-between sm:px-8">
                        <div><p class="text-xs font-bold uppercase tracking-[.16em] text-sky-300">Tracking result</p><h2 class="mt-2 text-2xl font-black">{{ $order->order_number }}</h2><p class="mt-1 text-sm text-slate-400">Placed {{ $order->created_at->format('d M Y, h:i A') }}</p></div>
                        <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-bold {{ match($order->status) { 'completed' => 'bg-emerald-400/15 text-emerald-300', 'cancelled', 'returned' => 'bg-rose-400/15 text-rose-300', 'shipped' => 'bg-violet-400/15 text-violet-300', default => 'bg-amber-400/15 text-amber-300' } }}">{{ App\OrderStatus::from($order->status)->label() }}</span>
                    </div>
                    <div class="p-6 sm:p-8">
                        @if ($isExceptional)
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5"><p class="font-bold text-rose-800">Order {{ App\OrderStatus::from($order->status)->label() }}</p><p class="mt-1 text-sm text-rose-700">This order is no longer following the standard delivery steps.</p></div>
                        @else
                            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                                @foreach ($steps as $index => $step)
                                    @php $isReached = $currentStep !== false && $index <= $currentStep; @endphp
                                    <div class="rounded-2xl border px-3 py-4 text-center {{ $isReached ? 'border-indigo-200 bg-indigo-50' : 'border-slate-200 bg-slate-50' }}"><span class="mx-auto grid h-8 w-8 place-items-center rounded-full text-xs font-black {{ $isReached ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500' }}">{{ $isReached ? '✓' : $index + 1 }}</span><p class="mt-2 text-xs font-bold {{ $isReached ? 'text-indigo-700' : 'text-slate-500' }}">{{ App\OrderStatus::from($step)->label() }}</p></div>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,.65fr)]">
                            <div class="space-y-6">
                                <section class="overflow-hidden rounded-2xl border border-slate-200">
                                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4"><h3 class="font-extrabold text-slate-900">Order items</h3></div>
                                    <div class="divide-y divide-slate-100">@foreach ($order->items as $item)<div class="flex items-start justify-between gap-4 px-5 py-4"><div><p class="font-semibold text-slate-800">{{ $item->product_title }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->quantity }} × ৳{{ number_format((float) $item->unit_price, 2) }}</p>@if ($item->product?->isDigital() && $item->product->digital_file_path)<a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('downloads.show', now()->addDays(30), ['order' => $order->id, 'item' => $item->id]) }}" class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-700">Download file &rarr;</a>@endif</div><strong class="shrink-0 text-sm">৳{{ number_format((float) $item->line_total, 2) }}</strong></div>@endforeach</div>
                                    <div class="space-y-2 border-t border-slate-200 bg-slate-50 px-5 py-4 text-sm"><div class="flex justify-between"><span class="text-slate-500">Subtotal</span><strong>৳{{ number_format((float) $order->subtotal, 2) }}</strong></div><div class="flex justify-between"><span class="text-slate-500">Delivery charge</span><strong>৳{{ number_format((float) $order->shipping_cost, 2) }}</strong></div><div class="flex justify-between border-t border-slate-200 pt-3 text-base"><span class="font-bold">Total</span><strong>৳{{ number_format((float) $order->total, 2) }}</strong></div></div>
                                </section>
                                <section class="rounded-2xl border border-slate-200 p-5"><h3 class="font-extrabold text-slate-900">{{ $order->shipping_address ? 'Delivery details' : 'Contact details' }}</h3><p class="mt-3 text-sm font-semibold text-slate-700">{{ $order->customer_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $order->customer_phone }}</p>@if ($order->shipping_address)<p class="mt-3 text-sm leading-6 text-slate-600">{{ $order->shipping_address }}</p>@else<p class="mt-3 text-sm text-slate-500">Digital order &mdash; no shipping address needed.</p>@endif</section>
                            </div>
                            <div class="space-y-6">
                                @if ($order->shipment)
                                    <section class="rounded-2xl border border-sky-200 bg-sky-50 p-5"><p class="text-xs font-bold uppercase tracking-[.14em] text-sky-600">Courier</p><h3 class="mt-2 font-extrabold text-slate-900">{{ $order->shipment->courierIntegration?->name ?? ucfirst($order->shipment->provider) }}</h3><dl class="mt-4 space-y-3 text-sm"><div><dt class="text-slate-500">Tracking code</dt><dd class="mt-1 font-bold text-slate-800">{{ $order->shipment->tracking_code ?: 'Pending assignment' }}</dd></div><div><dt class="text-slate-500">Courier status</dt><dd class="mt-1 font-bold capitalize text-slate-800">{{ str($order->shipment->status)->replace('_', ' ') }}</dd></div>@if ($order->shipment->last_synced_at)<div><dt class="text-slate-500">Last updated</dt><dd class="mt-1 font-semibold text-slate-700">{{ $order->shipment->last_synced_at->format('d M Y, h:i A') }}</dd></div>@endif</dl></section>
                                @endif
                                <section class="rounded-2xl border border-slate-200 p-5"><h3 class="font-extrabold text-slate-900">Status history</h3><ol class="mt-5 space-y-5">@foreach ($order->statusHistories->sortByDesc('created_at') as $history)<li class="relative border-l-2 border-indigo-200 pl-5"><span class="absolute -left-[7px] top-0 h-3 w-3 rounded-full bg-indigo-600 ring-4 ring-indigo-50"></span><p class="text-sm font-bold text-slate-800">{{ App\OrderStatus::from($history->to_status)->label() }}</p><time class="mt-1 block text-xs text-slate-500">{{ $history->created_at->format('d M Y, h:i A') }}</time></li>@endforeach</ol></section>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="mx-auto grid max-w-4xl gap-4 sm:grid-cols-3">
                    @foreach ([['1', 'Enter details', 'Use the order ID and checkout phone number.'], ['2', 'Verify order', 'We securely match both details.'], ['3', 'See progress', 'View status, items and courier updates.']] as [$number, $title, $copy])
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><span class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-600">{{ $number }}</span><h2 class="mt-4 font-extrabold text-slate-900">{{ $title }}</h2><p class="mt-2 text-sm leading-6 text-slate-500">{{ $copy }}</p></div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-storefront-layout>
