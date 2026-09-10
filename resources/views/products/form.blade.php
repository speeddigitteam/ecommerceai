@php($editing = $product->exists)
@php($selectedCategoryIds = old('category_ids', $editing ? $product->categories()->pluck('categories.id')->all() : []))
@php($specificationSections = old('specifications', $product->specifications ?? []))
@php($productQuestions = old('questions', $product->questions ?? []))
@php($variantRows = old('variants', $product->variants->map(fn ($variant) => [
    'id' => $variant->id,
    'sku' => $variant->sku,
    'price' => $variant->price,
    'sale_price' => $variant->sale_price,
    'stock_quantity' => $variant->stock_quantity,
    'image_path' => $variant->image_path,
    'options' => collect($variant->options ?? [])->map(fn ($value, $name) => ['name' => $name, 'value' => $value])->values()->all(),
])->values()->all()))
@php($wholesaleTierRows = collect(old('wholesale_tiers', $editing ? $product->wholesalePriceTiers->whereNull('product_variant_id')->map(fn ($tier) => ['minimum_quantity' => $tier->minimum_quantity, 'unit_price' => $tier->unit_price])->values()->all() : []))->pad(3, ['minimum_quantity' => '', 'unit_price' => '']))
<x-admin-layout title="{{ $editing ? 'Edit Product' : 'Add Product' }}">
    <x-slot:head>
        <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
        <style>
            .product-description-editor .ck.ck-editor { width: 100%; }
            .product-description-editor .ck.ck-toolbar { border-color: #e2e8f0; border-radius: .75rem .75rem 0 0; background: #f8fafc; padding: .5rem; }
            .product-description-editor .ck.ck-toolbar__items { flex-wrap: wrap; gap: .125rem; }
            .product-description-editor .ck.ck-button { border-radius: .5rem; }
            .product-description-editor .ck.ck-editor__main > .ck-editor__editable { min-height: 360px; border-color: #e2e8f0; border-radius: 0 0 .75rem .75rem; padding: 1.25rem 1.5rem; color: #0f172a; box-shadow: none; }
            .product-description-editor .ck.ck-editor__main > .ck-editor__editable.ck-focused { border-color: #6366f1; box-shadow: 0 0 0 3px rgb(99 102 241 / .12); }
            .product-description-editor .ck-content { font-size: .9375rem; line-height: 1.75; }
            .product-description-editor .ck-content h1, .product-description-editor .ck-content h2, .product-description-editor .ck-content h3 { color: inherit; font-weight: 700; line-height: 1.3; }
            .dark .product-description-editor .ck.ck-toolbar { border-color: #334155; background: #172033; }
            .dark .product-description-editor .ck.ck-editor__main > .ck-editor__editable { border-color: #334155; background: #1e293b; color: #e2e8f0; }
            .dark .product-description-editor .ck.ck-editor__main > .ck-editor__editable.ck-focused { border-color: #818cf8; box-shadow: 0 0 0 3px rgb(129 140 248 / .14); }
            .dark .product-description-editor .ck.ck-button, .dark .product-description-editor .ck.ck-dropdown__button { color: #e2e8f0; }
            .dark .product-description-editor .ck.ck-button:hover, .dark .product-description-editor .ck.ck-button.ck-on { background: #334155; }
            @media (max-width: 640px) { .product-description-editor .ck.ck-editor__main > .ck-editor__editable { min-height: 280px; padding: 1rem; } }
        </style>
    </x-slot:head>
        <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div><x-admin-sidebar />
        <main class="min-w-0 lg:pl-72"><x-admin-topbar /><form @if($editing) data-ds-editable data-ds-editable-start="edit" @endif method="POST" action="{{ $editing ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data">@csrf @if($editing) @method('PUT') @endif
            <div class="mx-auto max-w-[1500px] p-5 sm:p-8"><div class="mb-6 flex flex-wrap items-center justify-between gap-4"><div><a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600"><svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/></svg>Products</a><h1 class="mt-2 text-2xl font-bold">{{ $editing ? 'Edit product' : 'Add new product' }}</h1></div><a href="{{ $editing ? route('products.show', $product) : '#' }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">Preview</a></div>
                @if ($errors->any())<div class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">Please fix the highlighted fields.</div>@endif
                <x-ai-content-generator type="product" />
                <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="space-y-6">
                        <section x-data="{ title: @js(old('title', $product->title)), slug: @js(old('slug', $product->slug)), savedSlug: @js(old('slug', $product->slug)), editingSlug: false, init() { this.$watch('title', value => window.dispatchEvent(new CustomEvent('product-title-changed', { detail: value }))); this.$watch('slug', value => window.dispatchEvent(new CustomEvent('product-slug-changed', { detail: value }))); this.$nextTick(() => { window.dispatchEvent(new CustomEvent('product-title-changed', { detail: this.title })); window.dispatchEvent(new CustomEvent('product-slug-changed', { detail: this.slug })) }) }, slugify(value) { return value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') }, startEditing() { this.savedSlug = this.slug; this.editingSlug = true; this.$nextTick(() => this.$refs.slugInput.focus()) }, acceptSlug() { this.slug = this.slugify(this.slug || this.title); this.savedSlug = this.slug; this.editingSlug = false }, cancelEditing() { this.slug = this.savedSlug; this.editingSlug = false } }" class="ds-card ds-card-body">
                            <div>
                                <label class="ds-field-label">Product title</label>
                                <input name="title" x-model="title" @input="if (!editingSlug) slug = slugify(title)" required class="ds-input ds-control font-medium">
                                @error('title')<p class="ds-error">{{ $message }}</p>@enderror
                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                    <span class="font-semibold">Permalink:</span>
                                    <span class="inline-flex min-w-0 items-center text-indigo-600"><span class="whitespace-nowrap">{{ url('/') }}/</span><span x-show="!editingSlug" class="break-all"><span x-text="slug || 'product-slug'"></span>/</span><input x-cloak x-show="editingSlug" x-ref="slugInput" x-model="slug" @keydown.enter.prevent="acceptSlug()" @keydown.escape.prevent="cancelEditing()" class="min-w-48 rounded border border-indigo-500 bg-white px-2 py-1 text-sm text-slate-800 outline-none ring-2 ring-indigo-100 dark:bg-slate-800 dark:text-white"></span>
                                    <button x-show="!editingSlug" type="button" @click="startEditing()" class="rounded border border-indigo-500 px-2 py-1 font-semibold text-indigo-600">Edit</button>
                                    <button x-cloak x-show="editingSlug" type="button" @click="acceptSlug()" class="rounded border border-indigo-500 px-3 py-1 font-semibold text-indigo-600">OK</button>
                                    <button x-cloak x-show="editingSlug" type="button" @click="cancelEditing()" class="px-1 py-1 font-semibold text-indigo-600 underline">Cancel</button>
                                    <input type="hidden" name="slug" :value="slug">
                                </div>
                                @error('slug')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="product-description-editor mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/60 dark:border-slate-700 dark:bg-slate-900/30">
                                <div class="flex items-start gap-3 border-b border-slate-200 bg-white px-4 py-4 dark:border-slate-700 dark:bg-slate-800/60">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm3 5h8M8 13h8M8 17h5"/></svg>
                                    </span>
                                    <div><label for="product-description" class="ds-field-label">Description</label><p class="mt-0.5 text-xs leading-5 text-slate-500">Tell customers about features, benefits, specifications, and usage.</p></div>
                                </div>
                                <div class="p-4">
                                    <textarea id="product-description" name="description" rows="12">{{ old('description', $product->description) }}</textarea>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400">
                                    <span>Use headings and lists to make the description easy to scan.</span>
                                    <span class="inline-flex items-center gap-1.5 font-medium"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Rich text enabled</span>
                                </div>
                                @error('description')<p class="ds-error px-4 pb-4">{{ $message }}</p>@enderror
                            </div>
                        </section>
                        <section
                            x-data="{
                                sections: @js($specificationSections),
                                addSection() { this.sections.push({ title: '', items: [{ title: '', value: '' }] }) },
                                removeSection(sectionIndex) { this.sections.splice(sectionIndex, 1) },
                                addItem(sectionIndex) { this.sections[sectionIndex].items.push({ title: '', value: '' }) },
                                removeItem(sectionIndex, itemIndex) { this.sections[sectionIndex].items.splice(itemIndex, 1) }
                            }"
                            class="ds-card ds-card-body"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div><h2 class="ds-section-title">Specifications</h2><p class="ds-section-description">Add unlimited sections with title and value rows.</p></div>
                                <button type="button" @click="addSection()" class="ds-button-secondary">Add section</button>
                            </div>
                            <div class="mt-5 space-y-5">
                                <div x-show="sections.length === 0" class="rounded-2xl border border-dashed border-slate-300 px-5 py-8 text-center dark:border-slate-700"><p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No specification sections yet.</p><button type="button" @click="addSection()" class="mt-3 text-sm font-bold text-indigo-600">+ Add your first section</button></div>
                                <template x-for="(section, sectionIndex) in sections" :key="sectionIndex">
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                                        <div class="flex items-end gap-3 bg-slate-50 p-4 dark:bg-slate-800/60">
                                            <div class="min-w-0 flex-1"><label class="ds-field-label">Section title</label><input x-model="section.title" :name="`specifications[${sectionIndex}][title]`" class="ds-input ds-control" placeholder="e.g. General Information" required></div>
                                            <button type="button" @click="removeSection(sectionIndex)" class="mb-0.5 rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-500/10">Remove</button>
                                        </div>
                                        <div class="space-y-3 p-4">
                                            <template x-for="(item, itemIndex) in section.items" :key="itemIndex">
                                                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                                                    <div><label class="ds-field-label">Title</label><input x-model="item.title" :name="`specifications[${sectionIndex}][items][${itemIndex}][title]`" class="ds-input ds-control" placeholder="e.g. Battery Type" required></div>
                                                    <div><label class="ds-field-label">Value</label><input x-model="item.value" :name="`specifications[${sectionIndex}][items][${itemIndex}][value]`" class="ds-input ds-control" placeholder="e.g. LiFePO4" required></div>
                                                    <button type="button" @click="removeItem(sectionIndex, itemIndex)" x-show="section.items.length > 1" class="mb-0.5 rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-500/10">Remove</button>
                                                </div>
                                            </template>
                                            <button type="button" @click="addItem(sectionIndex)" class="mt-1 inline-flex items-center gap-2 rounded-lg border border-dashed border-indigo-300 px-3 py-2 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50 dark:border-indigo-500/40 dark:hover:bg-indigo-500/10">+ Add title and value</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            @error('specifications')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('specifications.*.title')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('specifications.*.items.*.title')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('specifications.*.items.*.value')<p class="ds-error">{{ $message }}</p>@enderror
                        </section>
                        <section
                            x-data="{
                                questions: @js($productQuestions),
                                addQuestion() { this.questions.push({ question: '', answer: '' }) },
                                removeQuestion(index) { this.questions.splice(index, 1) }
                            }"
                            class="ds-card ds-card-body"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div><h2 class="ds-section-title">Product Questions</h2><p class="ds-section-description">Add questions and answers that apply only to this product.</p></div>
                                <button type="button" @click="addQuestion()" class="ds-button-secondary">Add question</button>
                            </div>
                            <div class="mt-5 space-y-4">
                                <div x-show="questions.length === 0" class="rounded-2xl border border-dashed border-slate-300 px-5 py-8 text-center dark:border-slate-700"><p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No questions added yet.</p><button type="button" @click="addQuestion()" class="mt-3 text-sm font-bold text-indigo-600">+ Add the first question</button></div>
                                <template x-for="(item, index) in questions" :key="index">
                                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                                        <div class="flex items-end gap-3"><div class="min-w-0 flex-1"><label class="ds-field-label">Question</label><input x-model="item.question" :name="`questions[${index}][question]`" class="ds-input ds-control" placeholder="e.g. Does this product include a warranty?" required></div><button type="button" @click="removeQuestion(index)" class="mb-0.5 rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">Remove</button></div>
                                        <div class="mt-4"><label class="ds-field-label">Answer</label><textarea x-model="item.answer" :name="`questions[${index}][answer]`" rows="3" class="ds-textarea ds-control" placeholder="Write a clear answer for customers." required></textarea></div>
                                    </div>
                                </template>
                            </div>
                            @error('questions')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('questions.*.question')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('questions.*.answer')<p class="ds-error">{{ $message }}</p>@enderror
                        </section>
                        <section x-data="{ productType: @js(old('type', $product->type ?? 'physical')) }" x-init="window.addEventListener('product-type-changed', e => productType = e.detail)" class="ds-card ds-card-body">
                            <div>
                                <h2 class="ds-section-title">Product data</h2>
                                <p class="ds-section-description">Set pricing, inventory details, and how this product is measured.</p>
                            </div>
                            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
                                <div class="overflow-hidden rounded-2xl border border-indigo-100 bg-indigo-50/40 dark:border-indigo-500/20 dark:bg-indigo-500/5">
                                    <div class="flex items-center gap-3 border-b border-indigo-100 px-4 py-4 dark:border-indigo-500/20">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M15.5 8.5h-5a2 2 0 1 0 0 4h3a2 2 0 1 1 0 4h-5M12 6.5v2m0 8v2"/></svg>
                                        </span>
                                        <div><h3 class="text-sm font-bold">Pricing</h3><p class="mt-0.5 text-xs text-slate-500">Regular and discounted price</p></div>
                                    </div>
                                    <div class="space-y-4 p-4">
                                        <div><label for="regular-price" class="ds-field-label">Regular price</label><input id="regular-price" name="price" type="number" min="0" step="0.01" value="{{ old('price', $product->price) }}" class="ds-input ds-control" placeholder="0.00">@error('price')<p class="ds-error">{{ $message }}</p>@enderror</div>
                                        <div><div class="ds-field-header"><label for="sale-price" class="ds-field-label">Sale price</label><span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Optional</span></div><input id="sale-price" name="sale_price" type="number" min="0" step="0.01" value="{{ old('sale_price', $product->sale_price) }}" class="ds-input ds-control" placeholder="0.00">@error('sale_price')<p class="ds-error">{{ $message }}</p>@enderror</div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-amber-100 bg-amber-50/40 dark:border-amber-500/20 dark:bg-amber-500/5">
                                    <div class="flex items-center gap-3 border-b border-amber-100 px-4 py-4 dark:border-amber-500/20">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9ZM4.5 7.5 12 12l7.5-4.5M12 12v9"/></svg>
                                        </span>
                                        <div><h3 class="text-sm font-bold">Inventory</h3><p class="mt-0.5 text-xs text-slate-500">SKU and available stock</p></div>
                                    </div>
                                    <div class="space-y-4 p-4">
                                        <div><label for="product-sku" class="ds-field-label">SKU</label><input id="product-sku" name="sku" value="{{ old('sku', $product->sku) }}" class="ds-input ds-control font-mono" placeholder="e.g. SKU-1001">@error('sku')<p class="ds-error">{{ $message }}</p>@enderror</div>
                                        <div x-show="productType === 'physical'"><label for="stock-quantity" class="ds-field-label">Stock quantity <span class="text-rose-500">*</span></label><input id="stock-quantity" name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', $product->isDigital() ? 0 : ($product->stock_quantity ?? 0)) }}" :required="productType === 'physical'" class="ds-input ds-control" placeholder="0">@error('stock_quantity')<p class="ds-error">{{ $message }}</p>@enderror</div>
                                        <p x-cloak x-show="productType === 'digital'" class="rounded-xl bg-indigo-50 px-3 py-2.5 text-xs text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">Digital products are always available &mdash; no stock tracking needed.</p>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50/40 dark:border-emerald-500/20 dark:bg-emerald-500/5">
                                    <div class="flex items-center gap-3 border-b border-emerald-100 px-4 py-4 dark:border-emerald-500/20">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17V7a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3Zm4-9h8M8 12h5"/></svg>
                                        </span>
                                        <div><h3 class="text-sm font-bold">Measurement</h3><p class="mt-0.5 text-xs text-slate-500">How the product is sold</p></div>
                                    </div>
                                    <div class="p-4">
                                        <label for="product-unit" class="ds-field-label">Unit</label>
                                        <select id="product-unit" name="unit_id" class="ds-select ds-control"><option value="">Select unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>{{ $unit->name }}</option>@endforeach</select>
                                        <p class="ds-help">Choose piece, kilogram, litre, or another unit.</p>
                                        @error('unit_id')<p class="ds-error">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="ds-card ds-card-body">
                            <div><h2 class="ds-section-title">Wholesale pricing</h2><p class="ds-section-description">Set up to three quantity prices for approved wholesale customers. Leave unused rows empty.</p></div>
                            <div class="mt-5 grid gap-3">
                                @foreach($wholesaleTierRows as $index => $tier)
                                    <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-2">
                                        <div><label class="ds-field-label">Minimum quantity</label><input name="wholesale_tiers[{{ $index }}][minimum_quantity]" value="{{ $tier['minimum_quantity'] }}" type="number" min="2" class="ds-input ds-control"></div>
                                        <div><label class="ds-field-label">Wholesale unit price</label><input name="wholesale_tiers[{{ $index }}][unit_price]" value="{{ $tier['unit_price'] }}" type="number" min="0" step="0.01" class="ds-input ds-control"></div>
                                    </div>
                                @endforeach
                            </div>
                            @error('wholesale_tiers.*.minimum_quantity')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('wholesale_tiers.*.unit_price')<p class="ds-error">{{ $message }}</p>@enderror
                        </section>
                        <section
                            x-data="{
                                variants: @js($variantRows),
                                productType: @js(old('type', $product->type ?? 'physical')),
                                addVariant() { this.variants.push({ id: null, sku: '', price: '', sale_price: '', stock_quantity: 0, image_path: null, options: [{ name: '', value: '' }] }) },
                                removeVariant(index) { this.variants.splice(index, 1) },
                                addOption(index) { this.variants[index].options.push({ name: '', value: '' }) },
                                removeOption(index, optionIndex) { this.variants[index].options.splice(optionIndex, 1) }
                            }"
                            x-init="window.addEventListener('product-type-changed', e => productType = e.detail)"
                            x-show="productType === 'physical'"
                            class="ds-card ds-card-body"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div><h2 class="ds-section-title">Variants</h2><p class="ds-section-description">Add options like size or color, each with its own SKU, price, and stock. Leave empty to sell this product without variants.</p></div>
                                <button type="button" @click="addVariant()" class="ds-button-secondary">Add variant</button>
                            </div>
                            <div class="mt-5 space-y-5">
                                <div x-show="variants.length === 0" class="rounded-2xl border border-dashed border-slate-300 px-5 py-8 text-center dark:border-slate-700"><p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No variants yet.</p><p class="mt-1 text-xs text-slate-500">This product will use the pricing and stock fields above.</p><button type="button" @click="addVariant()" class="mt-3 text-sm font-bold text-indigo-600">+ Add your first variant</button></div>
                                <p x-cloak x-show="variants.length > 0" class="rounded-xl bg-amber-50 px-4 py-2.5 text-xs text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">When variants exist, the price, sale price, and stock quantity fields above are calculated automatically from the variants below.</p>
                                <template x-for="(variant, index) in variants" :key="index">
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                                        <div class="flex items-center justify-between gap-3 bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                            <span class="text-sm font-bold">Variant <span x-text="index + 1"></span></span>
                                            <button type="button" @click="removeVariant(index)" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-500/10">Remove</button>
                                        </div>
                                        <div class="space-y-4 p-4">
                                            <input type="hidden" :name="`variants[${index}][id]`" :value="variant.id">
                                            <div>
                                                <label class="ds-field-label">Options</label>
                                                <div class="space-y-2">
                                                    <template x-for="(option, optionIndex) in variant.options" :key="optionIndex">
                                                        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-center">
                                                            <input x-model="option.name" :name="`variants[${index}][options][${optionIndex}][name]`" class="ds-input ds-control" placeholder="e.g. Size" required>
                                                            <input x-model="option.value" :name="`variants[${index}][options][${optionIndex}][value]`" class="ds-input ds-control" placeholder="e.g. Large" required>
                                                            <button type="button" @click="removeOption(index, optionIndex)" x-show="variant.options.length > 1" class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-500/10">Remove</button>
                                                        </div>
                                                    </template>
                                                </div>
                                                <button type="button" @click="addOption(index)" class="mt-2 inline-flex items-center gap-2 rounded-lg border border-dashed border-indigo-300 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-50 dark:border-indigo-500/40 dark:hover:bg-indigo-500/10">+ Add option</button>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                                <div><label class="ds-field-label">SKU</label><input x-model="variant.sku" :name="`variants[${index}][sku]`" class="ds-input ds-control font-mono" placeholder="e.g. SKU-1001-M"></div>
                                                <div><label class="ds-field-label">Price</label><input x-model="variant.price" :name="`variants[${index}][price]`" type="number" min="0" step="0.01" class="ds-input ds-control" placeholder="0.00"></div>
                                                <div><label class="ds-field-label">Sale price</label><input x-model="variant.sale_price" :name="`variants[${index}][sale_price]`" type="number" min="0" step="0.01" class="ds-input ds-control" placeholder="0.00"></div>
                                                <div><label class="ds-field-label">Stock</label><input x-model="variant.stock_quantity" :name="`variants[${index}][stock_quantity]`" type="number" min="0" class="ds-input ds-control" placeholder="0" required></div>
                                            </div>
                                            <div>
                                                <label class="ds-field-label">Variant image</label>
                                                <div class="flex items-center gap-3">
                                                    <img x-cloak x-show="variant.image_path" :src="variant.image_path ? `{{ asset('storage') }}/${variant.image_path}` : ''" class="h-16 w-16 rounded-lg border border-slate-200 object-cover dark:border-slate-700" alt="">
                                                    <input type="file" :name="`variants[${index}][image]`" accept="image/png,image/jpeg,image/webp" class="block flex-1 text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                                                </div>
                                                <label x-cloak x-show="variant.image_path" class="mt-2 inline-flex items-center gap-2 text-xs text-rose-600"><input type="checkbox" :name="`variants[${index}][remove_image]`" value="1" @change="if ($event.target.checked) variant.image_path = null" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"> Remove current image</label>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            @error('variants')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('variants.*.sku')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('variants.*.sale_price')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('variants.*.stock_quantity')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('variants.*.options.*.name')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('variants.*.options.*.value')<p class="ds-error">{{ $message }}</p>@enderror
                        </section>
                        <section
                            x-data="{ productType: @js(old('type', $product->type ?? 'physical')) }"
                            x-init="window.addEventListener('product-type-changed', e => productType = e.detail)"
                            x-show="productType === 'digital'"
                            class="ds-card ds-card-body"
                        >
                            <div>
                                <h2 class="ds-section-title">Digital delivery</h2>
                                <p class="ds-section-description">Upload the file customers receive after ordering &mdash; an ebook, license key list, software archive, or similar.</p>
                            </div>
                            <div class="mt-5 space-y-3">
                                @if ($product->digital_file_path)
                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800/60">
                                        <div><p class="font-semibold">{{ $product->digital_file_name ?: basename($product->digital_file_path) }}</p><p class="mt-0.5 text-xs text-slate-500">Current file on this product</p></div>
                                        <label class="inline-flex items-center gap-2 text-xs text-rose-600"><input type="checkbox" name="remove_digital_file" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"> Remove file</label>
                                    </div>
                                @endif
                                <div>
                                    <label for="digital-file" class="ds-field-label">{{ $product->digital_file_path ? 'Replace file' : 'File' }}</label>
                                    <input id="digital-file" name="digital_file" type="file" class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                                    <p class="mt-2 text-xs text-slate-400">Max 100 MB. Delivered to customers as a secure download link after ordering.</p>
                                    @error('digital_file')<p class="ds-error">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </section>
                        <section class="ds-card ds-card-body" x-data="{ deliveryType: @js(old('delivery_charge_type', $product->delivery_charge_type ?? 'default')) }">
                            <h2 class="ds-section-title">Delivery charge</h2>
                            <label for="delivery-charge-type" class="ds-field-label mt-4">Delivery option</label>
                            <select id="delivery-charge-type" name="delivery_charge_type" x-model="deliveryType" class="ds-input ds-control">
                                <option value="default">Default â€” use delivery settings</option><option value="free">Free delivery</option><option value="custom">Custom charges</option>
                            </select>
                            @error('delivery_charge_type')<p class="ds-error">{{ $message }}</p>@enderror
                            <div x-show="deliveryType === 'custom'" class="mt-4 grid gap-4 sm:grid-cols-3">
                                @foreach (App\Services\DeliveryCharges::AREAS as $area => $label)
                                    <div><label class="ds-field-label" for="delivery-{{ $area }}">{{ $label }} (BDT)</label><input id="delivery-{{ $area }}" name="delivery_charges[{{ $area }}]" type="number" min="0" max="999999" step="0.01" :required="deliveryType === 'custom'" class="ds-input ds-control" value="{{ old('delivery_charges.'.$area, $product->delivery_charges[$area] ?? '') }}">@error('delivery_charges.'.$area)<p class="ds-error">{{ $message }}</p>@enderror</div>
                                @endforeach
                            </div>
                            <p class="mt-3 text-sm text-slate-500">Digital products always have free delivery. For multiple products, the highest charge applies once per order.</p>
                        </section>
                        <section class="ds-card ds-card-body">
                            <div>
                                <h2 class="font-bold">Product media</h2>
                                <p class="mt-1 text-sm text-slate-500">Add product images and video to make the listing more engaging.</p>
                            </div>
                            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
                                <div class="flex min-h-80 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 dark:border-slate-700 dark:bg-slate-900/40">
                                    <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-4 dark:border-slate-700">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.8"/><circle cx="9" cy="10" r="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m4 17 5-4 3 3 3-2 5 4"/></svg>
                                        </span>
                                        <div><h3 class="text-sm font-bold">Featured image</h3><p class="mt-0.5 text-xs text-slate-500">Main product cover</p></div>
                                    </div>
                                    <div class="flex flex-1 flex-col p-4">
                                        @if($product->featured_image_path)
                                            <img src="{{ asset('storage/'.$product->featured_image_path) }}" class="aspect-[4/3] w-full rounded-xl border border-slate-200 object-cover dark:border-slate-700" alt="{{ $product->title }} featured image">
                                        @else
                                            <div class="grid aspect-[4/3] w-full place-items-center rounded-xl border-2 border-dashed border-slate-300 bg-white text-center dark:border-slate-700 dark:bg-slate-900">
                                                <div class="px-4"><svg class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16.5V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10.5M8 10l2.5 2.5L14 9l6 6M3 20h18"/></svg><p class="mt-2 text-xs text-slate-400">No featured image yet</p></div>
                                            </div>
                                        @endif
                                        <input id="featured-image" name="featured_image" type="file" accept="image/png,image/jpeg,image/webp" class="mt-4 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                                        <p class="mt-2 text-xs text-slate-400">PNG, JPG or WebP &middot; Max 4 MB</p>
                                    </div>
                                </div>

                                <div class="flex min-h-80 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 dark:border-slate-700 dark:bg-slate-900/40">
                                    <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-4 dark:border-slate-700">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="5" width="14" height="14" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m17 9 4-2v10l-4-2M7 9h6M7 13h4"/></svg>
                                        </span>
                                        <div><h3 class="text-sm font-bold">Product video</h3><p class="mt-0.5 text-xs text-slate-500">Show it in action</p></div>
                                    </div>
                                    <div class="flex flex-1 flex-col p-4">
                                        @if($product->video_path)
                                            <video src="{{ asset('storage/'.$product->video_path) }}" controls class="aspect-[4/3] w-full rounded-xl bg-slate-950 object-contain"></video>
                                        @else
                                            <div class="grid aspect-[4/3] w-full place-items-center rounded-xl border-2 border-dashed border-slate-300 bg-white text-center dark:border-slate-700 dark:bg-slate-900">
                                                <div class="px-4"><span class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10"><svg class="ml-0.5 h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span><p class="mt-2 text-xs text-slate-400">No product video yet</p></div>
                                            </div>
                                        @endif
                                        <input id="product-video" name="video" type="file" accept="video/mp4,video/webm,video/quicktime" class="mt-4 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-500/15 dark:file:text-emerald-300">
                                        <p class="mt-2 text-xs text-slate-400">MP4, WebM or MOV &middot; Max 20 MB</p>
                                    </div>
                                </div>

                                <div class="flex min-h-80 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 dark:border-slate-700 dark:bg-slate-900/40">
                                    <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-4 dark:border-slate-700">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="14" height="14" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h11a3 3 0 0 0 3-3V7M7 11l2-2 4 4"/></svg>
                                        </span>
                                        <div><h3 class="text-sm font-bold">Product gallery</h3><p class="mt-0.5 text-xs text-slate-500">Up to 12 extra images</p></div>
                                    </div>
                                    <div class="flex flex-1 flex-col p-4">
                                        @if($product->gallery_paths)
                                            <div class="grid aspect-[4/3] grid-cols-3 content-start gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-900">
                                                @foreach($product->gallery_paths as $image)<img src="{{ asset('storage/'.$image) }}" class="aspect-square w-full rounded-lg object-cover" alt="{{ $product->title }} gallery image">@endforeach
                                            </div>
                                        @else
                                            <div class="grid aspect-[4/3] w-full place-items-center rounded-xl border-2 border-dashed border-slate-300 bg-white text-center dark:border-slate-700 dark:bg-slate-900">
                                                <div class="px-4"><svg class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h16v13H4zM8 7l1.5-3h5L16 7M8 12h.01M5 18l4-4 3 3 2-2 5 3"/></svg><p class="mt-2 text-xs text-slate-400">No gallery images yet</p></div>
                                            </div>
                                        @endif
                                        <input id="product-gallery" name="gallery[]" type="file" multiple accept="image/png,image/jpeg,image/webp" class="mt-4 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-2 file:font-semibold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-violet-500/15 dark:file:text-violet-300">
                                        <p class="mt-2 text-xs text-slate-400">Select multiple images &middot; Max 4 MB each</p>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section x-data="{ seoTitle: @js(old('seo_title', $product->seo_title ?? '')), metaDescription: @js(old('meta_description', $product->meta_description ?? '')), productTitle: @js(old('title', $product->title ?? '')), productSlug: @js(old('slug', $product->slug ?? '')) }" @product-title-changed.window="productTitle = $event.detail" @product-slug-changed.window="productSlug = $event.detail" class="ds-card ds-card-body">
                            <div><h2 class="font-bold">Search engine optimization</h2><p class="mt-1 text-sm text-slate-500">Optimize how this product appears in browsers and search results.</p></div>
                            <div class="ds-search-preview mt-5">
                                <p class="break-all text-xs text-emerald-700 dark:text-emerald-400">{{ url('/') }}/<span x-text="productSlug || 'product-slug'"></span>/</p>
                                <p class="mt-1 truncate text-lg font-medium text-blue-700 dark:text-blue-400"><span x-text="seoTitle || productTitle || 'Product SEO title'"></span> | {{ $websiteSettings?->site_name ?? config('app.name') }}</p>
                                <p class="mt-1 line-clamp-2 text-sm leading-5 text-slate-600 dark:text-slate-400" x-text="metaDescription || 'Your meta description preview will appear here.'"></p>
                            </div>
                            <div class="mt-5 grid gap-5">
                                <div x-data="{ keywords: @js(array_values(array_filter(array_map('trim', explode(',', old('focus_keyword', $product->focus_keyword ?? '')))))), keywordInput: '', notify() { this.$nextTick(() => window.dispatchEvent(new Event('product-seo-updated'))) }, addKeywords() { this.keywordInput.split(',').map(item => item.trim()).filter(Boolean).forEach(item => { const next = [...this.keywords, item].join(', '); if (!this.keywords.some(existing => existing.toLowerCase() === item.toLowerCase()) && next.length <= 100) this.keywords.push(item) }); this.keywordInput = ''; this.notify() }, removeKeyword(index) { this.keywords.splice(index, 1); this.notify() } }">
                                    <div class="ds-field-header"><label for="focus-keyword-input" class="ds-field-label">Focus keywords</label><span class="text-xs text-slate-400"><span x-text="keywords.length"></span> added</span></div>
                                    <input id="product-focus-keyword" name="focus_keyword" type="hidden" :value="keywords.join(', ')">
                                    <div class="ds-control rounded-xl border border-slate-300 bg-white p-2.5 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800">
                                        <div x-show="keywords.length" class="mb-2 flex flex-wrap gap-2"><template x-for="(keyword, index) in keywords" :key="keyword.toLowerCase()"><span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300"><span x-text="keyword"></span><button type="button" @click="removeKeyword(index)" class="text-indigo-400 transition hover:text-rose-600" :aria-label="`Remove ${keyword}`"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/></svg></button></span></template></div>
                                        <input id="focus-keyword-input" x-model="keywordInput" @input="if (keywordInput.includes(',')) addKeywords()" @keydown.enter.prevent="addKeywords()" @keydown.tab="addKeywords()" @blur="addKeywords()" type="text" class="w-full border-0 bg-transparent p-1 text-sm focus:ring-0 dark:text-slate-100" placeholder="Type a keyword, then press comma or Enter">
                                    </div>
                                    <p id="focus-keyword-feedback" class="ds-help">Add focus keywords with comma or Enter to start the live check.</p>
                                    @error('focus_keyword')<p class="ds-error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <div class="ds-field-header"><label for="product-seo-title" class="ds-field-label">SEO title</label><span class="text-xs leading-5 text-slate-400">Ideal: 50-60</span></div>
                                    <input id="product-seo-title" name="seo_title" x-model="seoTitle" maxlength="60" aria-describedby="product-seo-title-progress" class="ds-input ds-control">
                                    <div id="product-seo-title-progress" class="mt-3"><div class="h-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="progressbar" aria-valuemin="0" aria-valuemax="60" :aria-valuenow="seoTitle.length"><div class="h-full rounded-full transition-all duration-200" :class="seoTitle.length === 0 ? 'bg-slate-400' : seoTitle.length < 50 ? 'bg-amber-400' : seoTitle.length <= 60 ? 'bg-emerald-500' : 'bg-rose-600'" :style="`width: ${Math.min((seoTitle.length / 60) * 100, 100)}%`"></div></div><div class="mt-1.5 flex justify-between text-xs"><span :class="seoTitle.length === 0 ? 'text-slate-400' : seoTitle.length < 50 ? 'text-amber-600' : seoTitle.length <= 60 ? 'text-emerald-600' : 'font-semibold text-rose-600'" x-text="seoTitle.length === 0 ? 'Start typing' : seoTitle.length > 60 ? 'Too long' : seoTitle.length >= 50 ? 'Ideal length' : 'Keep writing'"></span><span class="tabular-nums text-slate-500"><span x-text="seoTitle.length"></span>/60</span></div></div>
                                    @error('seo_title')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <div class="ds-field-header"><label for="product-meta-description" class="ds-field-label">Meta description</label><span class="text-xs text-slate-400">Ideal: 150-160</span></div>
                                    <textarea id="product-meta-description" name="meta_description" x-model="metaDescription" maxlength="160" rows="3" aria-describedby="product-meta-description-progress" class="ds-textarea ds-control"></textarea>
                                    <div id="product-meta-description-progress" class="mt-3"><div class="h-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="progressbar" aria-valuemin="0" aria-valuemax="160" :aria-valuenow="metaDescription.length"><div class="h-full rounded-full transition-all duration-200" :class="metaDescription.length === 0 ? 'bg-slate-400' : metaDescription.length < 150 ? 'bg-amber-400' : metaDescription.length <= 160 ? 'bg-emerald-500' : 'bg-rose-600'" :style="`width: ${Math.min((metaDescription.length / 160) * 100, 100)}%`"></div></div><div class="mt-1.5 flex justify-between text-xs"><span :class="metaDescription.length === 0 ? 'text-slate-400' : metaDescription.length < 150 ? 'text-amber-600' : metaDescription.length <= 160 ? 'text-emerald-600' : 'font-semibold text-rose-600'" x-text="metaDescription.length === 0 ? 'Start typing' : metaDescription.length > 160 ? 'Too long' : metaDescription.length >= 150 ? 'Ideal length' : 'Keep writing'"></span><span class="tabular-nums text-slate-500"><span x-text="metaDescription.length"></span>/160</span></div></div>
                                    @error('meta_description')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </section>
                    </div>
                    <aside class="space-y-6 xl:sticky xl:top-24">
                        <section x-data="{ type: @js(old('type', $product->type ?? 'physical')) }" x-init="$watch('type', value => window.dispatchEvent(new CustomEvent('product-type-changed', { detail: value })))" class="ds-card overflow-hidden">
                            <div class="border-b border-slate-200 px-5 py-4 font-bold dark:border-slate-800">Publish</div>
                            <div class="space-y-4 p-5 text-sm">
                                <div class="flex items-center justify-between gap-3"><span>Product type:</span><select name="type" x-model="type" class="rounded-lg border-slate-300 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800"><option value="physical">Physical</option><option value="digital">Digital</option></select></div>
                                @error('type')<p class="ds-error">{{ $message }}</p>@enderror
                                <div class="flex items-center justify-between gap-3"><span>Status:</span><select name="status" class="rounded-lg border-slate-300 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800"><option value="draft" @selected(old('status', $product->status ?: 'draft') === 'draft')>Draft</option><option value="published" @selected(old('status', $product->status) === 'published')>Published</option></select></div>
                                <div class="flex items-center justify-between gap-3"><span>Visibility:</span><select name="visibility" class="rounded-lg border-slate-300 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800"><option value="public" @selected(old('visibility', $product->visibility ?: 'public') === 'public')>Public</option><option value="private" @selected(old('visibility', $product->visibility) === 'private')>Private</option></select></div>
                                <div class="flex items-center justify-between gap-3">
                                    <label for="product-published-at" class="shrink-0">Publish:</label>
                                    <input id="product-published-at" name="published_at" type="datetime-local" value="{{ old('published_at', $product->published_at?->format('Y-m-d\TH:i')) }}" class="min-w-0 max-w-56 flex-1 rounded-lg border-slate-300 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800">
                                </div>
                                <div id="product-seo-analysis" x-data="{ open: false }" data-has-featured-image="{{ $product->featured_image_path ? 'true' : 'false' }}" class="border-t border-slate-100 pt-4 dark:border-slate-800">
                                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="product-seo-checks" class="flex w-full items-center justify-between gap-3 rounded-lg text-left focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                                        <div><p class="font-semibold">On-page SEO score</p><p id="product-seo-status" class="mt-0.5 text-xs text-slate-500">Checking product content...</p></div>
                                        <span class="flex shrink-0 items-center gap-2"><span id="product-seo-score" class="text-2xl font-extrabold tabular-nums text-slate-400">0%</span><svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></span>
                                    </button>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="progressbar" aria-label="Product SEO score" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div id="product-seo-score-bar" class="h-full w-0 rounded-full bg-slate-400 transition-all duration-300"></div></div>
                                    <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500"><p id="product-seo-check-count">0 of 11 checks passed</p><span x-text="open ? 'Hide details' : 'View details'"></span></div>
                                    <ul id="product-seo-checks" x-cloak x-show="open" x-transition class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-xs dark:border-slate-800"></ul>
                                </div>
                            </div>
                            <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/30"><button class="ds-button-primary px-5">{{ $editing ? 'Update' : 'Publish' }}</button></div>
                        </section>
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-[#161f2e]">
                            <h2 class="font-bold">Categories</h2>
                            <div class="mt-4 overflow-hidden rounded-xl border border-slate-300 dark:border-slate-700"><div class="border-b border-slate-200 px-3 pt-2 dark:border-slate-700"><span class="inline-block border-b-2 border-indigo-600 px-2 pb-2 text-sm font-semibold text-indigo-600">All Categories</span></div><div id="product-category-options" class="max-h-64 overflow-y-auto p-2">@forelse($categories as $category)<x-product-category-checkbox :category="$category" :selected-category-ids="$selectedCategoryIds" />@empty<p data-empty-taxonomy class="p-3 text-sm text-slate-500">No categories available.</p>@endforelse</div></div>
                            @error('category_ids')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror @error('category_ids.*')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            <button type="button" data-quick-taxonomy="category" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700"><span>+</span> Add new category</button>
                        </section>
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-[#161f2e]">
                            <h2 class="font-bold">Brand</h2>
                            <div class="mt-4 overflow-hidden rounded-xl border border-slate-300 dark:border-slate-700"><div class="border-b border-slate-200 px-3 pt-2 dark:border-slate-700"><span class="inline-block border-b-2 border-indigo-600 px-2 pb-2 text-sm font-semibold text-indigo-600">All Brands</span></div><div id="product-brand-options" class="max-h-56 overflow-y-auto p-2"><label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800"><input name="brand_id" type="radio" value="" @checked(old('brand_id', $product->brand_id) === null) class="border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>No brand</span></label>@forelse($brands as $brand)<label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800"><input name="brand_id" type="radio" value="{{ $brand->id }}" @checked((string) old('brand_id', $product->brand_id) === (string) $brand->id) class="border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>{{ $brand->name }}</span></label>@empty<p data-empty-taxonomy class="p-3 text-sm text-slate-500">No brands available.</p>@endforelse</div></div>
                            @error('brand_id')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            <button type="button" data-quick-taxonomy="brand" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700"><span>+</span> Add new brand</button>
                        </section>
                        <section x-data="{ tags: @js(array_values(array_filter(array_map('trim', explode(',', old('tags', implode(', ', $product->tags ?? []))))))), tagInput: '', addTags() { this.tagInput.split(',').map(tag => tag.trim()).filter(Boolean).forEach(tag => { if (!this.tags.some(existing => existing.toLowerCase() === tag.toLowerCase())) this.tags.push(tag) }); this.tagInput = '' }, removeLastTag() { if (!this.tagInput && this.tags.length) this.tags.pop() } }" class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-[#161f2e]">
                            <h2 class="font-bold">Tags</h2>
                            <input name="tags" type="hidden" :value="tags.join(', ')">
                            <div class="mt-4 rounded-xl border border-slate-300 bg-white p-3 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800">
                                <div x-show="tags.length" class="mb-2 flex flex-wrap gap-2"><template x-for="(tag, index) in tags" :key="tag.toLowerCase()"><span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300"><span x-text="tag"></span><button type="button" @click="tags.splice(index, 1)" class="text-indigo-400 transition hover:text-rose-600" :aria-label="`Remove ${tag}`"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/></svg></button></span></template></div>
                                <label for="product-tag-input" class="sr-only">Add product tag</label><input id="product-tag-input" x-model="tagInput" @input="if (tagInput.includes(',')) addTags()" @keydown.enter.prevent="addTags()" @keydown.tab="addTags()" @keydown.backspace="removeLastTag()" @blur="addTags()" type="text" placeholder="Type a tag, then press comma or Enter" class="w-full border-0 bg-transparent p-1 text-sm focus:ring-0 dark:text-slate-100">
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500"><p>Press comma or Enter after each tag.</p><p><span x-text="tags.length"></span> tags</p></div>
                            @error('tags')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </section>
                    </aside>
                </div>
            </div>
        </form></main>
    </div>

    <div id="quick-taxonomy-modal" data-category-url="{{ route('categories.store') }}" data-brand-url="{{ route('brands.store') }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="quick-taxonomy-title">
        <div id="quick-taxonomy-dialog" class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-[#161f2e]">
            <div class="sticky top-0 z-10 flex items-center justify-between rounded-t-2xl border-b border-indigo-100 bg-indigo-50 px-5 py-4 dark:border-indigo-500/20 dark:bg-indigo-500/10">
                <h2 id="quick-taxonomy-title" class="text-xl font-bold">Add new category</h2>
                <button type="button" data-quick-taxonomy-close class="grid h-9 w-9 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Close modal"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/></svg></button>
            </div>
            <div class="p-5 sm:p-7">
              <div id="quick-taxonomy-form-panel" class="mt-6 space-y-6 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-[#161f2e] sm:p-7">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="quick-taxonomy-name" class="ds-field-label">Name <span class="text-rose-500">*</span></label>
                        <input id="quick-taxonomy-name" type="text" maxlength="100" class="ds-input ds-control" autocomplete="off" placeholder="e.g. Electronics">
                    </div>
                    <div id="quick-category-parent-field">
                        <label for="quick-category-parent" class="ds-field-label">Parent category</label>
                        <select id="quick-category-parent" class="ds-select ds-control"><option value="">None</option>@foreach($categories as $category)<x-category-option :category="$category" />@endforeach</select>
                        <p class="ds-help">Assign a parent term to create a hierarchy.</p>
                    </div>
                </div>
                <div>
                    <label for="quick-category-slug" class="ds-field-label">Slug</label>
                    <input id="quick-category-slug" type="text" maxlength="100" class="ds-input ds-control" placeholder="Leave empty to generate automatically">
                    <p class="ds-help">Used in the storefront URL. Leave empty to generate it from the name.</p>
                </div>
                <div id="quick-category-extra-fields" class="space-y-6">
                    <div>
                        <label for="quick-category-description" class="ds-field-label">Description</label>
                        <textarea id="quick-category-description" rows="4" class="ds-textarea ds-control" placeholder="Describe this category"></textarea>
                    </div>
                    <div class="ds-upload-zone">
                        <label for="quick-category-image" class="ds-field-label">Category image</label>
                        <p class="ds-help">Shown in the storefront category carousel.</p>
                        <input id="quick-category-image" type="file" accept="image/png,image/jpeg,image/webp" class="ds-file-input mt-3">
                    </div>
                </div>
                <p id="quick-taxonomy-error" class="ds-error hidden" role="alert"></p>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                    <button id="quick-taxonomy-cancel" type="button" data-quick-taxonomy-close class="ds-button-secondary">Cancel</button>
                    <button id="quick-taxonomy-submit" type="button" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">Save category</button>
                </div>
              </div>
            </div>
        </div>
    </div>

    <x-slot:scripts>
        <script>
            ClassicEditor.create(document.querySelector('#product-description'), {
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                        { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                        { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                        { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
                    ]
                },
                toolbar: {
                    items: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', 'blockQuote', 'insertTable', 'mediaEmbed', '|', 'undo', 'redo'],
                    shouldNotGroupWhenFull: true
                },
                table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] }
            }).then(editor => {
                window.productDescriptionEditor = editor;
                const notifySeoAnalyzer = () => window.dispatchEvent(new CustomEvent('product-description-changed', { detail: editor.getData() }));
                editor.model.document.on('change:data', notifySeoAnalyzer);
                notifySeoAnalyzer();
            }).catch(error => console.error(error));
        </script>
    </x-slot:scripts>
</x-admin-layout>
