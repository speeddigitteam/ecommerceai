<x-admin-layout title="Delivery Charge Settings">
    <div class="ds-page"><x-admin-sidebar />
        <main class="min-w-0 lg:pl-72"><x-admin-topbar />
            <div class="mx-auto max-w-4xl p-5 sm:p-8">
                <h1 class="text-2xl font-bold">Delivery Charge Settings</h1>
                <p class="mt-2 mb-6 text-sm text-slate-500">Default delivery charges per order. Products can use these rates, free delivery or custom rates.</p>
                @if(session('status'))<p class="mb-5 rounded-xl bg-emerald-50 p-4 text-emerald-700">{{ session('status') }}</p>@endif
                <form data-ds-editable method="POST" action="{{ route('settings.delivery-charges.update') }}" class="ds-card">
                    @csrf @method('PUT')
                    <div class="ds-card-body space-y-5">
                        @foreach (App\Services\DeliveryCharges::AREAS as $area => $label)
                            <div><label for="{{ $area }}" class="ds-field-label">{{ $label }} (BDT)</label><input id="{{ $area }}" name="delivery_charges[{{ $area }}]" type="number" min="0" max="999999" step="0.01" required class="ds-input ds-control" value="{{ old('delivery_charges.'.$area, $rates[$area]) }}">@error('delivery_charges.'.$area)<p class="ds-error">{{ $message }}</p>@enderror</div>
                        @endforeach
                        <p class="text-sm text-slate-500">For multiple products, the highest applicable charge is taken once. All-free and digital-only orders have no delivery charge.</p>
                    </div>
                    <div class="flex justify-end border-t border-slate-200 p-5"><button class="ds-button-primary">Save changes</button></div>
                </form>
            </div>
        </main>
    </div>
</x-admin-layout>
