<x-storefront-layout
    title="Best Selling Products"
    description="Shop best selling products, customer favourites and popular everyday essentials in Bangladesh."
    :canonical="route('storefront.best-selling')"
>
    <main class="min-h-screen bg-[#f8fafc] py-8 dark:bg-[#111827] sm:py-12 lg:py-16">
        <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('storefront.index') }}" class="font-medium transition hover:text-indigo-600 dark:hover:text-indigo-400">Home</a>
                <span aria-hidden="true">&rsaquo;</span>
                <span aria-current="page" class="text-slate-800 dark:text-slate-200">Best Selling Products</span>
            </nav>

            <header class="mt-6 flex flex-col gap-4 border-b border-slate-200 pb-7 dark:border-slate-800 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold text-orange-500">Customer favourites</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-4xl">Best Selling Products</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400">Explore our complete collection, ordered by the products customers purchase most.</p>
                </div>
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $products->total() }} products</p>
            </header>

            @if ($products->isNotEmpty())
                <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 lg:gap-5">
                    @foreach ($products as $product)
                        <div class="relative">
                            @if ((int) $product->sold_quantity > 0)
                                <span class="absolute left-3 top-3 z-10 rounded-full bg-orange-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">{{ (int) $product->sold_quantity }} sold</span>
                            @endif
                            <x-store-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
            @else
                <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-800/50">
                    <h2 class="text-xl font-bold text-slate-950 dark:text-white">No products available</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Published products will appear here.</p>
                    <a href="{{ route('storefront.index') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-700">Back to home</a>
                </div>
            @endif
        </div>
    </main>
</x-storefront-layout>