<x-storefront-layout title="Page not found">
    <div class="mx-auto flex min-h-[65vh] max-w-5xl items-center px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <section class="relative w-full overflow-hidden rounded-3xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm sm:px-12 sm:py-16">
            <div class="pointer-events-none absolute -left-20 -top-24 h-64 w-64 rounded-full bg-sky-100/70 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-20 h-64 w-64 rounded-full bg-indigo-100/70 blur-3xl"></div>

            <div class="relative mx-auto max-w-2xl">
                <div class="mx-auto grid h-24 w-24 place-items-center text-sky-500 sm:h-28 sm:w-28">
                    <svg class="h-12 w-12 sm:h-14 sm:w-14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.5 9.5h.01m5 0h.01M9 16c.8-1 1.8-1.5 3-1.5s2.2.5 3 1.5M5 3h10l4 4v14H5V3Zm10 0v5h5"/>
                    </svg>
                </div>

                <p class="mt-8 text-sm font-medium uppercase tracking-[.28em] text-sky-600">Error 404</p>
                <h1 class="mt-3 text-4xl font-normal tracking-tight text-slate-950 sm:text-6xl">Page not found</h1>
                <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-slate-500 sm:text-lg">The page you are looking for may have been moved, removed, or the address might be incorrect.</p>

                <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('storefront.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10 19-7-7 7-7M3 12h18"/></svg>
                        Back to home
                    </a>
                    <a href="{{ route('storefront.shop') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">Browse products</a>
                </div>
            </div>
        </section>
    </div>
</x-storefront-layout>
