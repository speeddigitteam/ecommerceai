<x-admin-layout title="Edit {{ $category->name }}">
    <div class="ds-page">
        <x-admin-sidebar />
        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-3xl p-5 sm:p-10">
                <a href="{{ route('blog.categories') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">&larr; Back to blog categories</a>
                <section class="ds-card ds-card-body mt-5">
                    <h1 class="text-2xl font-bold">Edit category</h1>
                    <form data-ds-editable data-ds-editable-start="edit" id="category-edit-form" method="POST" action="{{ route('blog.categories.update', $category) }}" enctype="multipart/form-data" class="mt-7 space-y-5">
                        @csrf
                        @method('PUT')
                        <div><label for="category-name" class="ds-field-label">Name</label><input id="category-name" name="name" value="{{ old('name', $category->name) }}" required class="ds-input ds-control">@error('name')<p class="ds-error">{{ $message }}</p>@enderror</div>
                        <div><label for="category-slug" class="ds-field-label">Slug</label><input id="category-slug" name="slug" value="{{ old('slug', $category->slug) }}" maxlength="100" class="ds-input ds-control" placeholder="Leave empty to generate automatically"><p class="ds-help">Used in the blog category URL.</p>@error('slug')<p class="ds-error">{{ $message }}</p>@enderror</div>
                        <div><label for="category-parent" class="ds-field-label">Parent category</label><select id="category-parent" name="parent_id" class="ds-select ds-control"><option value="">None</option>@foreach ($categories as $parent)<x-blog-category-option :category="$parent" :selected-id="old('parent_id', $category->parent_id)" :excluded-ids="$excludedCategoryIds" />@endforeach</select>@error('parent_id')<p class="ds-error">{{ $message }}</p>@enderror</div>
                        <div><label for="category-description" class="ds-field-label">Top description</label><textarea id="category-description" name="description" rows="4" class="ds-textarea ds-control">{{ old('description', $category->description) }}</textarea><p class="ds-help">Shown below the blog category breadcrumb and title.</p></div>
                        <div class="ds-upload-zone"><label for="category-navigation-image" class="ds-field-label">Category image</label><p class="ds-help">Uploading a new image replaces the current one.</p><input id="category-navigation-image" name="navigation_image" type="file" accept="image/png,image/jpeg,image/webp" class="ds-file-input mt-3">@if ($category->image_path)<img src="{{ asset('storage/'.$category->image_path) }}" alt="{{ $category->name }} image" class="mt-3 h-24 w-24 rounded-2xl bg-slate-100 object-contain p-2">@endif @error('navigation_image')<p class="ds-error">{{ $message }}</p>@enderror</div>
                        <div><label for="category-extra-description" class="ds-field-label">Bottom description</label><textarea id="category-extra-description" name="extra_description" rows="5" class="ds-textarea ds-control">{{ old('extra_description', $category->extra_description) }}</textarea><p class="ds-help">Shown after the blog post list and pagination.</p></div>
                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800"><a href="{{ route('blog.categories') }}" class="ds-button-secondary">Cancel</a><button class="ds-button-primary px-5">Save changes</button></div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</x-admin-layout>
