<x-admin-layout title="{{ $category->name }}">
    <div class="ds-page">
        <x-admin-sidebar />
        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-3xl p-5 sm:p-10">
                <a href="{{ route('blog.categories') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">&larr; Back to blog categories</a>
                <section class="ds-card ds-card-body mt-5">
                    <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Blog category</p><h1 class="mt-1 text-3xl font-bold">{{ $category->name }}</h1><p class="mt-2 text-sm text-slate-500">/{{ $category->slug }}</p></div><a href="{{ route('blog.categories.edit', $category) }}" class="ds-button-primary">Edit category</a></div>
                    @if ($category->image_path)<img src="{{ asset('storage/'.$category->image_path) }}" alt="{{ $category->name }}" class="mt-6 h-40 w-40 rounded-xl object-cover">@endif
                    <div class="mt-7 grid gap-5 border-t border-slate-100 pt-6 text-sm dark:border-slate-800 sm:grid-cols-2"><div><p class="text-slate-500">Parent category</p><p class="mt-1 font-medium">{{ $category->parent?->name ?? 'None' }}</p></div><div><p class="text-slate-500">Post count</p><p class="mt-1 font-medium">0</p></div><div class="sm:col-span-2"><p class="text-slate-500">Top description</p><div class="prose mt-1 max-w-none dark:prose-invert">{!! $category->description ?: 'No description added.' !!}</div></div><div class="sm:col-span-2"><p class="text-slate-500">Bottom description</p><div class="prose mt-1 max-w-none dark:prose-invert">{!! $category->extra_description ?: 'No description added.' !!}</div></div></div>
                </section>
            </div>
        </main>
    </div>
</x-admin-layout>
