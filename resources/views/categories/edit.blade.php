<x-admin-layout title="Edit {{ $category->name }} - Shopwise">
    <div class="ds-page">
        <x-admin-sidebar />
        <div class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <main class="mx-auto max-w-3xl p-5 sm:p-10">
                <a href="{{ route('categories.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">&larr; Back to categories</a>
                @if ($errors->any())
                    <div role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                        <p class="font-semibold">Category could not be updated.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <section class="ds-card ds-card-body mt-5">
                    <h1 class="text-2xl font-bold">Edit category</h1>
                    <form data-ds-editable data-ds-editable-start="edit" id="category-edit-form" method="POST" action="{{ route('categories.update', $category) }}" enctype="multipart/form-data" class="mt-7 space-y-5">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="category-name" class="ds-field-label">Name</label>
                            <input id="category-name" name="name" value="{{ old('name', $category->name) }}" required class="ds-input ds-control">
                            @error('name')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="category-slug" class="ds-field-label">Slug</label>
                            <input id="category-slug" name="slug" value="{{ old('slug', $category->slug) }}" maxlength="100" class="ds-input ds-control" placeholder="Leave empty to generate automatically"><p class="ds-help">Used in the storefront URL. Leave empty to generate it from the name.</p>
                            @error('slug')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="category-parent" class="ds-field-label">Parent category</label>
                            <select id="category-parent" name="parent_id" class="ds-select ds-control">
                                <option value="">None</option>
                                @foreach ($categories as $parent)
                                    <x-category-option :category="$parent" :selected-id="old('parent_id', $category->parent_id)" :excluded-ids="$excludedCategoryIds" />
                                @endforeach
                            </select>
                            @error('parent_id')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="category-description" class="ds-field-label">Top description</label>
                            <textarea id="category-description" name="description" rows="4" class="ds-textarea ds-control">{{ old('description', $category->description) }}</textarea>
                            <p class="ds-help">Shown below the category breadcrumb and title.</p>
                        </div>

                        <input name="display_type" value="default" type="hidden">

                        <div class="ds-upload-zone">
                            <label for="category-navigation-image" class="ds-field-label">Category image</label>
                            <p class="ds-help">Shown in the storefront category carousel. Uploading a new image replaces the current one.</p>
                            <input id="category-navigation-image" name="navigation_image" type="file" accept="image/png,image/jpeg,image/webp" class="ds-file-input mt-3">
                            @if ($category->navigation_image_path ?: $category->thumbnail_path)<img src="{{ asset('storage/'.($category->navigation_image_path ?: $category->thumbnail_path)) }}" alt="{{ $category->name }} image" class="mt-3 h-24 w-24 rounded-2xl bg-slate-100 object-contain p-2">@endif
                            @error('navigation_image')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="category-extra-description" class="ds-field-label">Bottom description</label>
                            <textarea id="category-extra-description" name="extra_description" rows="5" class="ds-textarea ds-control">{{ old('extra_description', $category->extra_description) }}</textarea>
                            <p class="ds-help">Shown after the product list and pagination.</p>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                            <a href="{{ route('categories.index') }}" class="ds-button-secondary">Cancel</a>
                            <button class="ds-button-primary px-5">Save changes</button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>
</x-admin-layout>
