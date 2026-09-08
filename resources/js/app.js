import Alpine from 'alpinejs';
window.storefrontChrome = () => ({
    darkMode: document.documentElement.classList.contains('dark'),
    init() {
        this.darkMode = localStorage.getItem('admin-theme') === 'dark' || (!localStorage.getItem('admin-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
    },
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('admin-theme', this.darkMode ? 'dark' : 'light');
    },
});

window.adminChrome = () => ({
    darkMode: document.documentElement.classList.contains('dark'),
    menuOpen: false,
    productsOpen: ['/products', '/categories', '/brands', '/units'].some((path) => window.location.pathname.startsWith(path)),
    init() {
        this.darkMode = localStorage.getItem('admin-theme') === 'dark' || (!localStorage.getItem('admin-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
    },
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('admin-theme', this.darkMode ? 'dark' : 'light');
    },
});

window.dashboard = () => ({
    taxonomyPanel: null,
    selectedCategories: [],
});

window.editableForm = (startEditing = false) => ({
    editing: startEditing,
    dirty: false,
    submitting: false,
    originalState: [],
    init() {
        this.$nextTick(() => {
            this.originalState = this.readState();
        });
    },
    readState() {
        return [...this.$refs.form.elements]
            .filter((control) => control.name && control.type !== 'submit' && control.type !== 'button')
            .map((control, index) => ({
                index,
                name: control.name,
                value: control.value,
                checked: control.checked ?? false,
                selectedIndex: control.selectedIndex ?? -1,
            }));
    },
    beginEdit() {
        this.originalState = this.readState();
        this.editing = true;
        this.dirty = false;
        this.$nextTick(() => this.$refs.form.querySelector('[data-editable-control]')?.focus());
    },
    trackChanges() {
        if (this.editing) {
            this.dirty = JSON.stringify(this.readState()) !== JSON.stringify(this.originalState);
        }
    },
    cancelEdit() {
        [...this.$refs.form.elements]
            .filter((control) => control.name && control.type !== 'submit' && control.type !== 'button')
            .forEach((control, index) => {
                const original = this.originalState[index];

                if (!original) return;

                control.value = original.value;
                if ('checked' in control) control.checked = original.checked;
                if (control.tagName === 'SELECT') control.selectedIndex = original.selectedIndex;
            });
        this.editing = false;
        this.dirty = false;
    },
    submit(event) {
        if (!this.editing || !this.dirty) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
});

window.Alpine = Alpine;
Alpine.start();

const editorToolbar = ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'];

const createEditor = (textarea) => {
    if (textarea && window.ClassicEditor && !textarea.dataset.editorReady) {
        textarea.dataset.editorReady = 'true';
        window.ClassicEditor.create(textarea, { toolbar: editorToolbar }).catch(() => {});
    }
};

const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

const enhancePermalink = (slugInput, nameInput) => {
    const field = slugInput.closest('div');
    const label = field.querySelector('label');
    const permalink = document.createElement('div');
    let savedSlug = slugInput.value;

    label?.remove();
    permalink.className = 'flex flex-wrap items-center gap-1.5 text-xs text-slate-500';
    permalink.dataset.permalink = '';
    permalink.innerHTML = `<span class='font-semibold'>Permalink:</span><span class='inline-flex min-w-0 items-center text-indigo-600'><span class='whitespace-nowrap'>${window.location.origin}/</span><span data-slug-display class='break-all'></span></span><button data-slug-edit type='button' class='rounded border border-indigo-500 px-2 py-1 font-semibold text-indigo-600'>Edit</button><button data-slug-ok type='button' class='hidden rounded border border-indigo-500 px-3 py-1 font-semibold text-indigo-600'>OK</button><button data-slug-cancel type='button' class='hidden px-1 py-1 font-semibold text-indigo-600 underline'>Cancel</button>`;
    slugInput.className = 'hidden min-w-48 rounded border border-indigo-500 bg-white px-2 py-1 text-sm text-slate-800 outline-none ring-2 ring-indigo-100 dark:bg-slate-800 dark:text-white';
    field.insertBefore(permalink, slugInput);
    permalink.querySelector('.inline-flex').append(slugInput);

    const display = permalink.querySelector('[data-slug-display]');
    const edit = permalink.querySelector('[data-slug-edit]');
    const ok = permalink.querySelector('[data-slug-ok]');
    const cancel = permalink.querySelector('[data-slug-cancel]');
    const update = () => { display.textContent = `${slugInput.value || 'category-slug'}/`; };
    const toggleEditing = (editing) => {
        display.classList.toggle('hidden', editing);
        slugInput.classList.toggle('hidden', !editing);
        edit.classList.toggle('hidden', editing);
        ok.classList.toggle('hidden', !editing);
        cancel.classList.toggle('hidden', !editing);
        if (editing) slugInput.focus();
    };
    const accept = () => {
        slugInput.value = slugify(slugInput.value || nameInput.value);
        slugInput.dispatchEvent(new Event('input'));
        savedSlug = slugInput.value;
        update();
        toggleEditing(false);
    };

    edit.addEventListener('click', () => { savedSlug = slugInput.value; toggleEditing(true); });
    ok.addEventListener('click', accept);
    cancel.addEventListener('click', () => { slugInput.value = savedSlug; update(); toggleEditing(false); });
    slugInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); accept(); }
        if (event.key === 'Escape') { event.preventDefault(); cancel.click(); }
    });
    slugInput.addEventListener('input', update);
    update();

    return update;
};

const prepareForm = (form) => {
    ['Display type', 'Thumbnail', 'Extra description', 'Category slider'].forEach((text) => {
        [...form.querySelectorAll('label')].find((label) => label.textContent.trim() === text)?.parentElement?.remove();
    });
    form.querySelector('[name="page_title_image"]')?.closest('.grid')?.remove();
    form.querySelector('[name="slug"]')?.closest('div')?.querySelector('p')?.remove();
    [...form.querySelectorAll('p')].forEach((paragraph) => {
        if (paragraph.textContent.includes('The description is not prominent') || paragraph.textContent.includes('Additional category description')) {
            paragraph.remove();
        }
        if (paragraph.textContent.includes('The term Jazz, for example')) {
            paragraph.textContent = 'Assign a parent term to create a hierarchy.';
        }
    });
    const name = form.querySelector('[name="name"]')?.closest('div');
    const parent = form.querySelector('[name="parent_id"]')?.closest('div');
    if (name && parent && name.parentElement === parent.parentElement) {
        const row = document.createElement('div');
        row.className = 'grid gap-4 sm:grid-cols-2';
        form.insertBefore(row, name);
        row.append(name, parent);
    }
};

const initializeCategoryPage = () => {
    const addPanel = document.getElementById('add-category');
    if (!addPanel) return;

    const addForm = addPanel.querySelector('form');
    prepareForm(addForm);
    const categoryName = addForm.querySelector('input[name=name]');
    const categorySlug = addForm.querySelector('input[name=slug]');
    const categorySlugField = categorySlug.closest('div');
    const updateCategoryPermalink = enhancePermalink(categorySlug, categoryName);
    const categoryPermalink = categorySlug.closest('[data-permalink]');
    categoryName.closest('div').append(categoryPermalink);
    categorySlugField.remove();
    let categorySlugEdited = false;
    categoryName.addEventListener('input', () => {
        if (!categorySlugEdited) categorySlug.value = slugify(categoryName.value);
        updateCategoryPermalink();
    });
    categorySlug.addEventListener('input', () => { categorySlugEdited = true; });
    const categoryImageHelp = addForm.querySelector('input[name=navigation_image]')?.closest('.ds-upload-zone')?.querySelector('.ds-help');
    if (categoryImageHelp) categoryImageHelp.textContent = 'Recommended: 600 × 600 px square image. PNG, JPG or WebP; maximum 2 MB.';
    createEditor(addForm.querySelector('[name="description"]'));
    createEditor(addForm.querySelector('[name="extra_description"]'));
    [...addForm.querySelectorAll('button')].find((button) => button.textContent.trim() === 'Add new category')?.replaceChildren('Save category');

    const emptyCell = [...document.querySelectorAll('td')].find((cell) => cell.textContent.includes('No categories yet.'));
    if (emptyCell) {
        emptyCell.innerHTML = '<p>No categories yet.</p><a href="#add-category" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add new category</a>';
    }

    const input = document.querySelector('input[placeholder="Search"]');
    const searchForm = input?.closest('form');
    const itemCount = [...document.querySelectorAll('span')].find((item) => /^\d+ items$/.test(item.textContent.trim()));
    if (searchForm && itemCount) {
        searchForm.className = 'flex shrink-0 items-center gap-2';
        const wrapper = document.createElement('div');
        wrapper.className = 'relative shrink-0';
        input.className = 'w-36 rounded-lg border-slate-300 bg-white py-2 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 sm:w-44';
        input.style.paddingLeft = '2.5rem';
        wrapper.innerHTML = '<svg class="pointer-events-none absolute h-4 w-4 text-slate-700 dark:text-slate-300" style="left:.75rem;top:50%;transform:translateY(-50%)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m20 20-4-4"/></svg>';
        input.replaceWith(wrapper);
        wrapper.append(input);
        itemCount.replaceWith(searchForm);
    }

    const dialog = document.querySelector('[role="dialog"]');
    const modal = dialog?.parentElement;
    const content = dialog?.lastElementChild;
    const header = dialog?.firstElementChild;
    if (!dialog || !modal || !content || !header) return;
    const show = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.classList.add('category-modal-open'); };
    const openAdd = () => {
        header.className = 'sticky top-0 z-10 flex items-center justify-between rounded-t-2xl border-b border-indigo-100 bg-indigo-50 px-5 py-3 dark:border-indigo-500/20 dark:bg-indigo-500/10';
        content.replaceChildren(addPanel); addPanel.classList.remove('hidden'); addPanel.style.display = 'block'; dialog.querySelector('h2').textContent = 'Add new category'; show();
    };
    document.querySelectorAll('a[href="#add-category"]').forEach((link) => link.addEventListener('click', (event) => { event.preventDefault(); openAdd(); }));
    document.querySelectorAll('[data-category-edit]').forEach((link) => link.addEventListener('click', async (event) => {
        event.preventDefault();
        const [editResponse, pageResponse] = await Promise.all([fetch(link.href), fetch(window.location.href)]);
        if (!editResponse.ok || !pageResponse.ok) { window.location.assign(link.href); return; }
        const parser = new DOMParser();
        const editForm = parser.parseFromString(await editResponse.text(), 'text/html').querySelector('form[action*="/categories/"]');
        const form = parser.parseFromString(await pageResponse.text(), 'text/html').querySelector('#add-category form');
        if (!editForm || !form) { window.location.assign(link.href); return; }
        prepareForm(form);
        ['name', 'slug', 'description', 'extra_description', 'parent_id'].forEach((name) => {
            const target = form.querySelector(`[name="${name}"]`); const source = editForm.querySelector(`[name="${name}"]`);
            if (target && source) {
                if (name === 'parent_id') target.innerHTML = source.innerHTML;
                target.value = source.value;
            }
        });
        const editName = form.querySelector('input[name=name]');
        const editSlug = form.querySelector('input[name=slug]');
        const editSlugField = editSlug.closest('div');
        enhancePermalink(editSlug, editName);
        editName.closest('div').append(editSlug.closest('[data-permalink]'));
        editSlugField.remove();
        form.className = 'space-y-4';
        const editImageHelp = form.querySelector('input[name=navigation_image]')?.closest('.ds-upload-zone')?.querySelector('.ds-help');
        if (editImageHelp) editImageHelp.textContent = 'Recommended: 600 x 600 px square image. PNG, JPG or WebP; maximum 2 MB.';
        form.action = editForm.action;
        form.insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">');
        [...form.querySelectorAll('button')].find((button) => button.textContent.trim() === 'Add new category')?.replaceChildren('Save changes');
        const panel = document.createElement('div');
        panel.className = 'rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-[#161f2e] sm:p-5';
        panel.append(form); content.replaceChildren(panel);
        header.className = 'sticky top-0 z-10 flex items-center justify-between rounded-t-2xl border-b border-amber-100 bg-amber-50 px-5 py-3 dark:border-amber-500/20 dark:bg-amber-500/10';
        dialog.querySelector('h2').textContent = 'Edit category';
        createEditor(form.querySelector('[name="description"]'));
        createEditor(form.querySelector('[name="extra_description"]'));
        show();
    }));
};

const initializeBrandPermalink = () => {
    if (!window.location.pathname.startsWith('/brands')) return;

    const form = document.querySelector('form input[name=slug]')?.closest('form');
    const brandName = form?.querySelector('input[name=name]');
    const brandSlug = form?.querySelector('input[name=slug]');
    if (!form || !brandName || !brandSlug) return;

    const updateBrandPermalink = enhancePermalink(brandSlug, brandName);
    let brandSlugEdited = Boolean(brandSlug.value);
    brandName.addEventListener('input', () => {
        if (!brandSlugEdited) brandSlug.value = slugify(brandName.value);
        updateBrandPermalink();
    });
    brandSlug.addEventListener('input', () => { brandSlugEdited = true; });
    document.querySelectorAll('button').forEach((button) => {
        if (button.textContent.includes('Add new brand') || button.title === 'Edit brand') {
            button.addEventListener('click', () => window.setTimeout(() => {
                brandSlugEdited = Boolean(brandSlug.value);
                updateBrandPermalink();
            }));
        }
    });
};

const initializeProductTaxonomyQuickAdd = () => {
    const modal = document.getElementById('quick-taxonomy-modal');
    if (!modal) return;

    const dialog = document.getElementById('quick-taxonomy-dialog');
    const title = document.getElementById('quick-taxonomy-title');
    const nameInput = document.getElementById('quick-taxonomy-name');
    const nameRow = nameInput.closest('.grid');
    const parentField = document.getElementById('quick-category-parent-field');
    const parentSelect = document.getElementById('quick-category-parent');
    const categoryExtraFields = document.getElementById('quick-category-extra-fields');
    const taxonomySlug = document.getElementById('quick-category-slug');
    const categoryDescription = document.getElementById('quick-category-description');
    const categoryImage = document.getElementById('quick-category-image');
    const quickCategoryImageHelp = categoryImage.closest('.ds-upload-zone')?.querySelector('.ds-help');
    if (quickCategoryImageHelp) quickCategoryImageHelp.textContent = 'Recommended: 600 × 600 px square image. PNG, JPG or WebP; maximum 2 MB.';
    const error = document.getElementById('quick-taxonomy-error');
    const cancel = document.getElementById('quick-taxonomy-cancel');
    const submit = document.getElementById('quick-taxonomy-submit');
    let taxonomy = 'category';
    let quickCategorySlugEdited = false;
    const updateQuickCategoryPermalink = enhancePermalink(taxonomySlug, nameInput);

    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    const open = (type) => {
        taxonomy = type;
        title.textContent = type === 'category' ? 'Add new category' : 'Add new brand';
        nameInput.placeholder = type === 'category' ? 'e.g. Electronics' : 'e.g. Apple';
        nameInput.value = '';
        parentSelect.value = '';
        taxonomySlug.value = '';
        updateQuickCategoryPermalink();
        quickCategorySlugEdited = false;
        categoryDescription.value = '';
        categoryImage.value = '';
        parentField.classList.toggle('hidden', type !== 'category');
        categoryExtraFields.classList.remove('hidden');
        categoryDescription.closest('div').classList.toggle('hidden', type !== 'category');
        categoryImage.closest('.ds-upload-zone').classList.toggle('hidden', type !== 'category');
        nameRow.classList.toggle('sm:grid-cols-2', type === 'category');
        dialog.classList.toggle('max-w-4xl', type === 'category');
        dialog.classList.toggle('max-w-md', type !== 'category');
        cancel.classList.toggle('hidden', type === 'category');
        submit.textContent = type === 'category' ? 'Save category' : 'Save brand';
        error.textContent = '';
        error.classList.add('hidden');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => nameInput.focus(), 50);
    };

    const optionLabel = (name, input) => {
        const label = document.createElement('label');
        label.className = 'flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800';
        const text = document.createElement('span');
        text.textContent = name;
        label.append(input, text);
        return label;
    };

    const addCategory = (category) => {
        const container = document.getElementById('product-category-options');
        container.querySelector('[data-empty-taxonomy]')?.remove();
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'category_ids[]';
        checkbox.value = category.id;
        checkbox.checked = true;
        checkbox.className = 'rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
        const selectedParent = parentSelect.options[parentSelect.selectedIndex];
        const parentPrefix = category.parent_id && selectedParent ? selectedParent.textContent.match(/^(?:Ã¢â‚¬â€\s*)*/)?.[0] ?? '' : '';
        const depth = category.parent_id ? (parentPrefix.match(/Ã¢â‚¬â€/g)?.length ?? 0) + 1 : 0;
        const categoryLabel = optionLabel(category.name, checkbox);
        categoryLabel.style.paddingLeft = `${0.5 + (depth * 1.1)}rem`;
        container.append(categoryLabel);

        const parentOption = document.createElement('option');
        parentOption.value = category.id;
        parentOption.textContent = `${'Ã¢â‚¬â€ '.repeat(depth)}${category.name}`;
        parentSelect.append(parentOption);
        window.dispatchEvent(new Event('product-seo-updated'));
    };

    const addBrand = (brand) => {
        const container = document.getElementById('product-brand-options');
        container.querySelector('[data-empty-taxonomy]')?.remove();
        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'brand_id';
        radio.value = brand.id;
        radio.checked = true;
        radio.className = 'border-slate-300 text-indigo-600 focus:ring-indigo-500';
        container.append(optionLabel(brand.name, radio));
    };

    const create = async () => {
        const name = nameInput.value.trim();
        if (!name) {
            error.textContent = 'Please enter a name.';
            error.classList.remove('hidden');
            nameInput.focus();
            return;
        }

        submit.disabled = true;
        submit.textContent = 'Saving...';
        error.classList.add('hidden');

        try {
            const payload = new FormData();
            payload.append('name', name);
            if (taxonomySlug.value.trim()) payload.append('slug', taxonomySlug.value.trim());
            if (taxonomy === 'category') {
                if (parentSelect.value) payload.append('parent_id', parentSelect.value);
                if (categoryDescription.value.trim()) payload.append('description', categoryDescription.value.trim());
                if (categoryImage.files[0]) payload.append('navigation_image', categoryImage.files[0]);
            }

            const response = await fetch(modal.dataset[`${taxonomy}Url`], {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: payload,
            });
            const result = await response.json();

            if (!response.ok) {
                const validationMessage = Object.values(result.errors ?? {}).flat()[0];
                throw new Error(validationMessage ?? result.message ?? 'Unable to create this item.');
            }

            if (taxonomy === 'category') addCategory(result.category);
            else addBrand(result.brand);
            close();
        } catch (exception) {
            error.textContent = exception.message;
            error.classList.remove('hidden');
        } finally {
            submit.disabled = false;
            submit.textContent = taxonomy === 'category' ? 'Save category' : 'Save brand';
        }
    };

    document.querySelectorAll('[data-quick-taxonomy]').forEach((button) => button.addEventListener('click', () => open(button.dataset.quickTaxonomy)));
    nameInput.addEventListener('input', () => {
        if (!quickCategorySlugEdited) taxonomySlug.value = slugify(nameInput.value);
        updateQuickCategoryPermalink();
    });
    taxonomySlug.addEventListener('input', () => { quickCategorySlugEdited = true; });
    modal.querySelectorAll('[data-quick-taxonomy-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    nameInput.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); create(); } });
    submit.addEventListener('click', create);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
};

const initializeProductSeoAnalysis = () => {
    const analysis = document.getElementById('product-seo-analysis');
    const form = document.querySelector('form[action*="/products"]');
    if (!analysis || !form) return;

    const scoreElement = document.getElementById('product-seo-score');
    const scoreBar = document.getElementById('product-seo-score-bar');
    const statusElement = document.getElementById('product-seo-status');
    const checkCount = document.getElementById('product-seo-check-count');
    const checkList = document.getElementById('product-seo-checks');
    const focusKeywordFeedback = document.getElementById('focus-keyword-feedback');
    let editorDescription = form.querySelector('[name="description"]')?.value ?? '';
    let frame = null;

    const evaluate = () => {
        frame = null;
        const productTitle = form.querySelector('[name="title"]')?.value.trim() ?? '';
        const seoTitle = form.querySelector('[name="seo_title"]')?.value.trim() || productTitle;
        const metaDescription = form.querySelector('[name="meta_description"]')?.value.trim() ?? '';
        const focusKeywords = (form.querySelector('[name="focus_keyword"]')?.value ?? '').split(',')
            .map((keyword) => keyword.trim().toLowerCase().replace(/\s+/g, ' '))
            .filter(Boolean);
        const slug = form.querySelector('[name="slug"]')?.value.trim() ?? '';
        const contentDocument = new DOMParser().parseFromString(editorDescription, 'text/html');
        const descriptionText = contentDocument.body.textContent.replace(/\s+/g, ' ').trim();
        const descriptionLength = descriptionText.length;
        const links = [...contentDocument.querySelectorAll('a[href]')].map((link) => link.getAttribute('href')?.trim()).filter(Boolean);
        const hasInternalLink = links.some((href) => {
            try { return href.startsWith('/') || new URL(href, window.location.origin).origin === window.location.origin; } catch { return false; }
        });
        const hasExternalLink = links.some((href) => {
            try { return /^https?:\/\//i.test(href) && new URL(href).origin !== window.location.origin; } catch { return false; }
        });
        const hasH1 = contentDocument.querySelector('h1') !== null;
        const hasH2 = contentDocument.querySelector('h2') !== null;
        const keywordSources = { title: seoTitle, meta: metaDescription, content: descriptionText };
        const keywordResults = focusKeywords.map((keyword) => ({
            keyword,
            locations: Object.entries(keywordSources)
                .filter(([, value]) => value.toLowerCase().replace(/\s+/g, ' ').includes(keyword))
                .map(([location]) => location),
        }));
        const keywordPlacements = keywordResults.reduce((total, result) => total + result.locations.length, 0);
        const possibleKeywordPlacements = focusKeywords.length * Object.keys(keywordSources).length;
        const keywordCoverage = possibleKeywordPlacements ? keywordPlacements / possibleKeywordPlacements : 0;
        const fullyPlacedKeywords = keywordResults.filter((result) => result.locations.length === 3).length;
        const hasImage = analysis.dataset.hasFeaturedImage === 'true' || (form.querySelector('[name="featured_image"]')?.files.length ?? 0) > 0;
        const hasCategory = form.querySelectorAll('[name="category_ids[]"]:checked').length > 0;

        const checks = [
            {
                label: 'SEO title',
                detail: `${seoTitle.length}/60 characters Ã‚Â· ideal 50Ã¢â‚¬â€œ60`,
                earned: seoTitle.length >= 50 && seoTitle.length <= 60 ? 15 : seoTitle.length >= 30 && seoTitle.length <= 65 ? 10 : 0,
                maximum: 15,
            },
            {
                label: 'Meta description',
                detail: `${metaDescription.length}/160 characters Ã‚Â· ideal 150Ã¢â‚¬â€œ160`,
                earned: metaDescription.length >= 150 && metaDescription.length <= 160 ? 15 : metaDescription.length >= 120 && metaDescription.length <= 170 ? 10 : 0,
                maximum: 15,
            },
            {
                label: 'Clean product URL',
                detail: slug ? `/${slug}` : 'Add a product title or slug',
                earned: slug.length >= 3 && slug.length <= 75 && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug) ? 10 : 0,
                maximum: 10,
            },
            {
                label: 'Detailed description',
                detail: `${descriptionLength} characters Ã‚Â· target at least 300`,
                earned: descriptionLength >= 300 ? 15 : descriptionLength >= 150 ? 8 : 0,
                maximum: 15,
            },
            {
                label: 'Featured image',
                detail: hasImage ? 'Image is ready for search and sharing' : 'Add a featured image',
                earned: hasImage ? 10 : 0,
                maximum: 10,
            },
            {
                label: 'Product category',
                detail: hasCategory ? 'At least one category selected' : 'Select at least one category',
                earned: hasCategory ? 5 : 0,
                maximum: 5,
            },
            {
                label: 'H1 heading in content',
                detail: hasH1 ? 'Content includes an H1 heading' : 'Add one H1 heading in the description',
                earned: hasH1 ? 5 : 0,
                maximum: 5,
            },
            {
                label: 'H2 heading in content',
                detail: hasH2 ? 'Content includes an H2 heading' : 'Add at least one H2 subheading',
                earned: hasH2 ? 5 : 0,
                maximum: 5,
            },
            {
                label: 'Internal link',
                detail: hasInternalLink ? 'Content links to another page on this site' : 'Add a link to a relevant page on this site',
                earned: hasInternalLink ? 5 : 0,
                maximum: 5,
            },
            {
                label: 'External link',
                detail: hasExternalLink ? 'Content includes a relevant external source' : 'Add a useful link to a trusted external source',
                earned: hasExternalLink ? 5 : 0,
                maximum: 5,
            },
            {
                label: 'Focus keyword placement',
                detail: focusKeywords.length ? `${fullyPlacedKeywords}/${focusKeywords.length} keywords fully placed Ã‚Â· ${keywordPlacements}/${possibleKeywordPlacements} total placements` : 'Add focus keywords and use each in title, meta, and content',
                earned: keywordCoverage === 1 ? 10 : keywordCoverage >= 0.66 ? 6 : keywordCoverage > 0 ? 3 : 0,
                maximum: 10,
            },
        ];

        if (!focusKeywords.length) {
            focusKeywordFeedback.textContent = 'Add focus keywords with comma or Enter to start the live check.';
            focusKeywordFeedback.className = 'ds-help';
        } else if (keywordCoverage === 1) {
            focusKeywordFeedback.textContent = `All ${focusKeywords.length} focus keywords appear in the SEO title, meta description, and content.`;
            focusKeywordFeedback.className = 'mt-2 text-xs leading-5 text-emerald-600 dark:text-emerald-400';
        } else {
            const missingSummary = keywordResults.map((result) => {
                const missing = Object.keys(keywordSources).filter((location) => !result.locations.includes(location));
                return `Ã¢â‚¬Å“${result.keyword}Ã¢â‚¬Â Ã¢â€ â€™ ${missing.length ? `add to ${missing.join(', ')}` : 'complete'}`;
            }).join('; ');
            focusKeywordFeedback.textContent = missingSummary;
            focusKeywordFeedback.className = `mt-2 text-xs leading-5 ${keywordPlacements ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'}`;
        }

        const score = checks.reduce((total, check) => total + check.earned, 0);
        const passed = checks.filter((check) => check.earned === check.maximum).length;
        const rating = score >= 90
            ? { text: 'Excellent on-page SEO', textClass: 'text-emerald-600 dark:text-emerald-400', barClass: 'bg-emerald-500' }
            : score >= 70
                ? { text: 'Good Ã¢â‚¬â€ a few improvements remain', textClass: 'text-indigo-600 dark:text-indigo-400', barClass: 'bg-indigo-500' }
                : score >= 50
                    ? { text: 'Needs improvement', textClass: 'text-amber-600 dark:text-amber-400', barClass: 'bg-amber-500' }
                    : { text: 'SEO setup is incomplete', textClass: 'text-rose-600 dark:text-rose-400', barClass: 'bg-rose-500' };

        scoreElement.textContent = `${score}%`;
        scoreElement.className = `text-2xl font-extrabold tabular-nums ${rating.textClass}`;
        statusElement.textContent = rating.text;
        statusElement.className = `mt-0.5 text-xs ${rating.textClass}`;
        scoreBar.style.width = `${score}%`;
        scoreBar.className = `h-full rounded-full transition-all duration-300 ${rating.barClass}`;
        scoreBar.parentElement.setAttribute('aria-valuenow', score);
        checkCount.textContent = `${passed} of ${checks.length} checks passed`;

        checkList.replaceChildren(...checks.map((check) => {
            const passedCheck = check.earned === check.maximum;
            const partialCheck = check.earned > 0 && !passedCheck;
            const item = document.createElement('li');
            item.className = 'flex items-start gap-2';
            const icon = document.createElement('span');
            icon.className = `mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded-full text-[10px] font-bold ${passedCheck ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : partialCheck ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'}`;
            icon.textContent = passedCheck ? 'Ã¢Å“â€œ' : partialCheck ? '!' : 'Ãƒâ€”';
            const copy = document.createElement('span');
            const label = document.createElement('strong');
            label.className = 'block font-semibold text-slate-700 dark:text-slate-200';
            label.textContent = check.label;
            const detail = document.createElement('span');
            detail.className = 'mt-0.5 block leading-4 text-slate-500 dark:text-slate-400';
            detail.textContent = check.detail;
            copy.append(label, detail);
            item.append(icon, copy);
            return item;
        }));
    };

    const scheduleEvaluation = () => {
        if (frame) window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(evaluate);
    };

    form.addEventListener('input', scheduleEvaluation);
    form.addEventListener('change', scheduleEvaluation);
    window.addEventListener('product-description-changed', (event) => {
        editorDescription = event.detail;
        scheduleEvaluation();
    });
    window.addEventListener('product-seo-updated', scheduleEvaluation);
    scheduleEvaluation();
};

const initializeStorefrontInfiniteProducts = () => {
    const grid = document.getElementById('storefront-product-grid');
    const loader = document.getElementById('storefront-product-loader');
    const sentinel = loader?.querySelector('[data-infinite-sentinel]');
    const loadingIndicator = loader?.querySelector('[data-loading-indicator]');
    const shopLink = document.getElementById('storefront-shop-link');

    if (!grid || !loader || !sentinel || !('IntersectionObserver' in window)) return;

    const limit = Number(grid.dataset.productLimit || 100);
    const total = Number(grid.dataset.productTotal || 0);
    let nextUrl = grid.dataset.nextUrl;
    let loaded = grid.querySelectorAll('[data-store-product-card]').length;
    let loading = false;

    const finish = (reachedLimit = false) => {
        observer.disconnect();
        sentinel.remove();
        loadingIndicator?.classList.add('hidden');
        loader.classList.add('hidden');

        if (reachedLimit && total > limit) {
            shopLink?.classList.remove('hidden');
        }
    };

    const loadNextPage = async () => {
        if (loading || !nextUrl) return;

        loading = true;
        loadingIndicator?.classList.remove('hidden');
        loadingIndicator?.classList.add('flex');

        try {
            const response = await fetch(nextUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Unable to load products.');

            const template = document.createElement('template');
            template.innerHTML = await response.text();
            const cards = [...template.content.querySelectorAll('[data-store-product-card]')];
            const remaining = Math.max(0, limit - loaded);
            const cardsToAppend = cards.slice(0, remaining);
            const meta = template.content.querySelector('[data-infinite-meta]');

            grid.append(...cardsToAppend);
            loaded += cardsToAppend.length;
            nextUrl = meta?.dataset.nextUrl || '';
            grid.dataset.nextUrl = nextUrl;

            if (loaded >= limit) {
                finish(true);
            } else if (!nextUrl || cardsToAppend.length === 0) {
                finish(false);
            }
        } catch (error) {
            observer.disconnect();
            sentinel.remove();
            loader.textContent = 'More products could not be loaded. Refresh the page to try again.';
            loader.className = 'mt-8 text-center text-sm font-medium text-rose-600';
        } finally {
            loading = false;
            loadingIndicator?.classList.add('hidden');
            loadingIndicator?.classList.remove('flex');
        }
    };

    const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) loadNextPage();
    }, { rootMargin: '300px 0px' });

    observer.observe(sentinel);
};

const initializeDesignSystemEditableForms = () => {
    document.querySelectorAll('form[data-ds-editable]').forEach((form) => {
        const controls = [...form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), textarea, select')]
            .filter((control) => !control.closest('[data-ds-editable-ignore]'));
        const saveButton = form.querySelector('button[type="submit"], input[type="submit"]')
            || [...form.querySelectorAll('button:not([type])')].at(-1);

        if (!controls.length || !saveButton) return;

        const actionContainer = saveButton.parentElement;
        const formButtons = [...form.querySelectorAll('button[type="button"]')]
            .filter((button) => !button.closest('[data-ds-editable-ignore]'));
        const legacyActionLinks = [...actionContainer.querySelectorAll('a')];
        const originalSaveText = saveButton.textContent.trim();
        const displays = [];
        let editing = form.dataset.dsEditableStart === 'edit';
        let baseline = '';

        const serialize = () => JSON.stringify(controls.map((control) => ({
            value: control.value,
            checked: control.checked ?? false,
            selectedIndex: control.selectedIndex ?? -1,
        })));
        const displayText = (control) => {
            if (control.type === 'password') return control.value ? '••••••••' : 'Not set';
            if (control.type === 'file') return control.files?.[0]?.name || 'No new file selected';
            if (control.type === 'checkbox' || control.type === 'radio') return control.checked ? 'Yes' : 'No';
            if (control.tagName === 'SELECT') return control.selectedOptions[0]?.textContent.trim() || 'Not selected';
            return control.value.trim() || 'Not provided';
        };
        const updateDisplays = () => displays.forEach(({ control, display }) => {
            if (control.dataset.dsRichText !== undefined) {
                const documentFragment = new DOMParser().parseFromString(control.value, 'text/html');
                display.textContent = documentFragment.body.textContent.trim() || 'Not provided';
                return;
            }

            display.textContent = displayText(control);
        });
        const setDirtyState = () => {
            const dirty = editing && serialize() !== baseline;
            saveButton.disabled = !dirty;
            saveButton.textContent = dirty ? originalSaveText : 'No changes';
            status.textContent = dirty ? 'You have unsaved changes.' : 'Change a field to enable saving.';
            status.classList.toggle('text-amber-600', dirty);
            status.classList.toggle('dark:text-amber-400', dirty);
        };
        const setMode = (nextEditing) => {
            editing = nextEditing;
            form.dataset.dsMode = editing ? 'edit' : 'view';
            controls.forEach((control) => {
                control.hidden = !editing;
                control.disabled = !editing;
                const editor = control.nextElementSibling;
                if (editor?.classList.contains('ck-editor')) editor.hidden = !editing;
            });
            formButtons.forEach((button) => {
                if (button !== editButton && button !== cancelButton) button.hidden = !editing;
            });
            legacyActionLinks.forEach((link) => {
                link.hidden = true;
            });
            displays.forEach(({ display }) => {
                display.hidden = editing;
            });
            editButton.hidden = editing;
            cancelButton.hidden = !editing;
            saveButton.hidden = !editing;
            status.hidden = !editing;

            if (editing) {
                baseline = serialize();
                setDirtyState();
                window.setTimeout(() => controls[0]?.focus(), 0);
            } else {
                updateDisplays();
            }
        };

        controls.forEach((control) => {
            const display = document.createElement('div');
            display.className = 'ds-readonly-value ds-generated-readonly';
            control.insertAdjacentElement('afterend', display);
            displays.push({ control, display });
        });

        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'ds-button-secondary';
        editButton.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.9 3.1 4 4L7 21H3v-4L16.9 3.1Z"/></svg>Edit';

        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = 'ds-button-secondary';
        cancelButton.textContent = 'Cancel editing';

        const status = document.createElement('p');
        status.className = 'mr-auto text-xs font-medium text-slate-500 dark:text-slate-400';

        actionContainer.prepend(status, editButton, cancelButton);
        editButton.addEventListener('click', () => setMode(true));
        cancelButton.addEventListener('click', () => {
            form.reset();
            controls.forEach((control) => control.dispatchEvent(new Event('change', { bubbles: true })));
            setMode(false);
        });
        form.addEventListener('input', setDirtyState);
        form.addEventListener('change', setDirtyState);
        form.addEventListener('ds-editor-ready', (event) => {
            event.detail.wrapper.hidden = !editing;
            event.detail.editor.model.document.on('change:data', () => {
                event.detail.control.value = event.detail.editor.getData();
                setDirtyState();
            });
        });
        form.addEventListener('submit', (event) => {
            if (!editing || serialize() === baseline) event.preventDefault();
        });

        updateDisplays();
        setMode(editing);
    });
};

const initializeDesignSystemDirtySubmitForms = () => {
    document.querySelectorAll('form[data-ds-dirty-submit]:not([data-ds-editable])').forEach((form) => {
        const controls = [...form.elements].filter((control) => control.name && !['_token', '_method'].includes(control.name));
        const saveButton = form.querySelector('button[type="submit"], input[type="submit"]')
            || [...form.querySelectorAll('button:not([type])')].at(-1);
        let baseline = '';

        if (!controls.length || !saveButton) return;

        const serialize = () => JSON.stringify(controls.map((control) => ({
            value: control.value,
            checked: control.checked ?? false,
        })));
        const resetBaseline = () => {
            baseline = serialize();
            saveButton.disabled = true;
        };
        const update = () => {
            saveButton.disabled = serialize() === baseline;
        };

        form.addEventListener('focusin', (event) => {
            if (!form.contains(event.relatedTarget)) resetBaseline();
        });
        form.addEventListener('pointerdown', (event) => {
            if (!form.contains(document.activeElement) && form.contains(event.target)) resetBaseline();
        }, true);
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.addEventListener('submit', (event) => {
            if (serialize() === baseline) event.preventDefault();
        });
        resetBaseline();
    });
};

document.addEventListener('DOMContentLoaded', initializeCategoryPage);
document.addEventListener('DOMContentLoaded', initializeBrandPermalink);
document.addEventListener('DOMContentLoaded', initializeProductTaxonomyQuickAdd);
document.addEventListener('DOMContentLoaded', initializeProductSeoAnalysis);
document.addEventListener('DOMContentLoaded', initializeStorefrontInfiniteProducts);
document.addEventListener('DOMContentLoaded', initializeDesignSystemEditableForms);
document.addEventListener('DOMContentLoaded', initializeDesignSystemDirtySubmitForms);
