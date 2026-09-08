<x-admin-layout title="Blog Categories">
    <x-slot:head><script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script></x-slot:head>
    <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />
        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-7xl p-5 sm:p-8">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div><p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Blog</p><h1 class="text-2xl font-bold">Blog categories</h1></div>
                    <a href="#add-category" class="ds-button-primary inline-flex items-center gap-2"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add new category</a>
                </div>
                @if (session('status'))<div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>@endif
                <section class="ds-card overflow-hidden">
                    <div class="flex flex-col gap-4 border-b border-slate-200 p-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-bold">Category list</h2><p class="mt-1 text-sm text-slate-500">Manage and organize blog categories.</p></div><form class="flex gap-2"><input class="w-48 rounded-lg border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" placeholder="Search" type="search"><button class="rounded-lg border border-indigo-600 px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-600 hover:text-white">Search</button></form></div>
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50"><div class="flex gap-2"><select class="rounded-md border-slate-300 bg-white py-2 text-sm dark:border-slate-700 dark:bg-slate-800"><option>Bulk actions</option><option>Delete</option></select><button class="rounded-md border border-indigo-600 px-3 py-2 text-sm text-indigo-600">Apply</button></div><span class="text-sm text-slate-500">{{ $categories->count() }} items</span></div>
                    <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-700 dark:bg-slate-800/60"><tr><th class="px-3 py-3"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></th><th class="px-2 py-3">Image</th><th class="px-3 py-3">Name</th><th class="px-3 py-3">Description</th><th class="px-3 py-3">Slug</th><th class="px-3 py-3 text-center">Count</th><th class="px-3 py-3"></th></tr></thead><tbody>@forelse ($categories as $category)<x-blog-category-table-row :category="$category" />@empty<tr><td colspan="7" class="p-10 text-center text-slate-500">No categories yet. Create your first one with the form below.</td></tr>@endforelse</tbody></table></div>
                </section>
                <section id="add-category" class="mt-6 hidden rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-[#161f2e] sm:p-5"><form method="POST" action="{{ route('blog.categories.store') }}" enctype="multipart/form-data" class="space-y-4">@csrf
                    <div><label class="ds-field-label">Name <span class="text-rose-500">*</span></label><input name="name" value="{{ old('name') }}" required class="ds-input ds-control" placeholder="e.g. Technology">@error('name')<p class="ds-error">{{ $message }}</p>@enderror</div>
                    <div><label class="ds-field-label">Slug</label><input name="slug" value="{{ old('slug') }}" maxlength="100" class="ds-input ds-control" placeholder="Leave empty to generate automatically"><p class="ds-help">Used in the blog category URL. Leave empty to generate it from the name.</p>@error('slug')<p class="ds-error">{{ $message }}</p>@enderror</div>
                    <div><label class="ds-field-label">Parent category</label><select name="parent_id" class="ds-select ds-control"><option value="">None</option>@foreach ($categories as $category)<x-blog-category-option :category="$category" :selected-id="old('parent_id')" />@endforeach</select><p class="ds-help">Assign a parent term to create a hierarchy.</p>@error('parent_id')<p class="ds-error">{{ $message }}</p>@enderror</div>
                    <div><label class="ds-field-label">Top description</label><textarea name="description" rows="4" class="ds-textarea ds-control">{{ old('description') }}</textarea><p class="ds-help">Shown below the blog category breadcrumb and title.</p></div>
                    <div><label class="ds-field-label">Bottom description</label><textarea name="extra_description" rows="5" class="ds-textarea ds-control">{{ old('extra_description') }}</textarea><p class="ds-help">Shown after the blog post list and pagination.</p></div>
                    <div class="ds-upload-zone"><label class="ds-field-label">Category image</label><p class="ds-help">Use a square PNG, WebP or JPG image.</p><input name="navigation_image" type="file" accept="image/png,image/jpeg,image/webp" class="ds-file-input mt-3">@error('navigation_image')<p class="ds-error">{{ $message }}</p>@enderror</div>
                    <div class="flex justify-end border-t border-slate-100 pt-5 dark:border-slate-800"><button class="ds-button-primary inline-flex items-center gap-2">Add new category</button></div>
                </form></section>
            </div>
        </main>
    </div>
    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" aria-hidden="true"><div role="dialog" aria-modal="true" class="category-editor-modal max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-[#161f2e]"><div class="sticky top-0 z-10 flex items-center justify-between border-b px-5 py-3"><h2 class="text-lg font-bold">Add new category</h2><x-modal-close-button onclick="this.closest('.fixed').classList.add('hidden')" /></div><div class="p-3 sm:p-4"></div></div></div>
</x-admin-layout>
