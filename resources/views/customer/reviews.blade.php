<x-customer-layout title="Reviews">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="flex flex-col gap-2 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-[#08264b]">My Product Reviews</h2>
                <p class="mt-1 text-sm text-slate-500">You can review products from completed orders only.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500">{{ $products->count() }} eligible {{ Str::plural('product', $products->count()) }}</span>
        </div>

        @if(session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="mt-6 space-y-6">
            @forelse($products as $product)
                @php($review = $reviews->get($product->id))
                <article id="product-{{ $product->id }}" class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200">
                    <header class="flex items-center gap-4 bg-slate-50 px-4 py-4 sm:px-6">
                        <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
                            @if($product->featured_image_path)<img src="{{ asset('storage/'.$product->featured_image_path) }}" alt="{{ $product->title }}" class="h-full w-full object-contain p-1">@else<span class="text-xs text-slate-400">No image</span>@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('catalog.show', $product->slug) }}" class="line-clamp-2 font-bold text-[#08264b] hover:text-blue-600">{{ $product->title }}</a>
                            <p class="mt-1 text-xs font-semibold {{ $review ? 'text-emerald-600' : 'text-amber-600' }}">{{ $review ? 'Your review is published' : 'Waiting for your review' }}</p>
                        </div>
                    </header>

                    <form @if($review) data-ds-editable data-ds-editable-start="edit" @endif method="POST" action="{{ $review ? route('customer.reviews.update', $review) : route('customer.reviews.store', $product) }}" enctype="multipart/form-data" x-data="{ previews: [] }" class="space-y-5 p-4 sm:p-6">
                        @csrf
                        @if($review) @method('PUT') @endif
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Rating</span>
                            <select name="rating" required class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 sm:max-w-xs">
                                @foreach(range(5, 1) as $rating)<option value="{{ $rating }}" @selected((int) old('rating', $review?->rating ?? 5) === $rating)>{{ $rating }} {{ Str::plural('star', $rating) }}</option>@endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Your review</span>
                            <textarea name="body" rows="4" required minlength="10" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Share your experience with this product...">{{ old('body', $review?->body) }}</textarea>
                        </label>

                        @if($review && filled($review->image_paths))
                            <fieldset>
                                <legend class="mb-2 text-sm font-bold text-slate-700">Current images</legend>
                                <div class="flex flex-wrap gap-3">
                                    @foreach($review->image_paths as $path)
                                        <label class="group relative h-24 w-24 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                            <img src="{{ asset('storage/'.$path) }}" alt="Review image" class="h-full w-full object-cover">
                                            <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-slate-950/75 px-2 py-1 text-[10px] font-bold text-white"><input type="checkbox" name="remove_images[]" value="{{ $path }}" class="rounded border-white/50 text-rose-500 focus:ring-rose-500"> Remove</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">{{ $review ? 'Add more images' : 'Review images' }} <span class="font-normal text-slate-400">(optional)</span></span>
                            <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" @change="previews.forEach(url => URL.revokeObjectURL(url)); previews = Array.from($event.target.files).slice(0, 5).map(file => URL.createObjectURL(file))" class="block w-full rounded-xl border border-slate-300 text-sm text-slate-500 file:mr-4 file:border-0 file:bg-blue-50 file:px-4 file:py-3 file:font-bold file:text-blue-700">
                            <span class="mt-1.5 block text-xs text-slate-400">Up to 5 JPG, PNG or WebP images; maximum 4 MB each.</span>
                        </label>
                        <div x-show="previews.length" x-cloak class="flex flex-wrap gap-3"><template x-for="url in previews" :key="url"><img :src="url" alt="Selected review image preview" class="h-24 w-24 rounded-xl border border-slate-200 object-cover"></template></div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="rounded-xl bg-[#1685f8] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">{{ $review ? 'Update Review' : 'Publish Review' }}</button>
                            @if($review)
                                <button type="submit" form="delete-review-{{ $review->id }}" onclick="return confirm('Delete this review?')" class="rounded-xl border border-rose-200 px-5 py-2.5 text-sm font-bold text-rose-600 transition hover:bg-rose-50">Delete</button>
                            @endif
                        </div>
                    </form>
                    @if($review)<form id="delete-review-{{ $review->id }}" method="POST" action="{{ route('customer.reviews.destroy', $review) }}" class="hidden">@csrf @method('DELETE')</form>@endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                    <h3 class="font-bold text-[#08264b]">No products to review yet</h3>
                    <p class="mt-2 text-sm text-slate-500">Products will appear here after your order is completed.</p>
                    <a href="{{ route('storefront.shop') }}" class="mt-5 inline-flex rounded-xl bg-[#1685f8] px-5 py-2.5 text-sm font-bold text-white">Continue Shopping</a>
                </div>
            @endforelse
        </div>
    </section>
</x-customer-layout>
