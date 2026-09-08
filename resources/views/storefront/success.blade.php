<x-storefront-layout title="Order confirmed">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:py-20">
        <section class="rounded-3xl border border-slate-200 bg-white px-5 py-10 text-center shadow-sm sm:px-12 sm:py-14">
            <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-emerald-100 text-emerald-600"><svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m5 13 4 4L19 7"/></svg></div>
            <p class="mt-6 text-sm font-extrabold uppercase tracking-[.2em] text-emerald-600">Order confirmed</p>
            <h1 class="mt-3 text-3xl font-black sm:text-5xl">Thank you for your order!</h1>
            <p class="mt-4 leading-7 text-slate-600">We will contact you at <strong>{{ $order->customer_phone }}</strong> to confirm delivery.</p>
            <div class="mt-8 bg-slate-50 px-5 py-1 text-left sm:px-6">
                <div class="flex justify-between gap-4 border-b border-slate-200 py-4"><span class="text-sm text-slate-500">Order number</span><strong>{{ $order->order_number }}</strong></div>
                <div class="flex justify-between gap-4 border-b border-slate-200 py-4"><span class="text-sm text-slate-500">Items total ({{ $order->items->sum('quantity') }})</span><strong><span class="currency-symbol">৳</span>{{ number_format((float) $order->subtotal, 2) }}</strong></div>
                <div class="flex justify-between gap-4 border-b border-slate-200 py-4"><span class="text-sm text-slate-500">Delivery charge</span><strong><span class="currency-symbol">৳</span>{{ number_format((float) $order->shipping_cost, 2) }}</strong></div>
                <div class="flex justify-between gap-4 py-4"><span class="text-sm font-semibold text-slate-700">Order total</span><strong class="text-xl"><span class="currency-symbol">৳</span>{{ number_format((float) $order->total, 2) }}</strong></div>
            </div>
            @php($digitalItems = $order->items->filter(fn ($item) => $item->product?->isDigital() && $item->product->digital_file_path))
            @if ($digitalItems->isNotEmpty())
                <div class="mt-8 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-5 text-left">
                    <h2 class="font-extrabold text-indigo-900">Your downloads</h2>
                    <div class="mt-3 space-y-2">
                        @foreach ($digitalItems as $item)
                            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('downloads.show', now()->addDays(30), ['order' => $order->id, 'item' => $item->id]) }}" class="flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50">
                                <span>{{ $item->product_title }}</span>
                                <span class="inline-flex items-center gap-1.5">Download <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0 4-4m-4 4-4-4M4 20h16"/></svg></span>
                            </a>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-indigo-600">Links stay valid for 30 days. You can also get them again from Track Order.</p>
                </div>
            @endif
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <form method="POST" action="{{ route('storefront.orders.track.show') }}">@csrf<input type="hidden" name="order_number" value="{{ $order->order_number }}"><input type="hidden" name="customer_phone" value="{{ $order->customer_phone }}"><button class="inline-flex rounded-xl bg-indigo-600 px-7 py-3.5 font-extrabold text-white transition hover:bg-indigo-700">Track order</button></form>
                <a href="{{ route('storefront.index') }}" class="inline-flex rounded-xl border border-slate-200 px-7 py-3.5 font-extrabold text-slate-700 transition hover:bg-slate-50">Continue shopping</a>
            </div>
        </section>
    </div>
</x-storefront-layout>
