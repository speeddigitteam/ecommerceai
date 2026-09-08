<x-admin-layout title="Website Content">
    <x-slot:head>
        <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
        <style>
            .website-content-editor .ck.ck-editor { width: 100%; }
            .website-content-editor .ck.ck-toolbar { border-color: #e2e8f0; border-radius: .75rem .75rem 0 0; background: #f8fafc; padding: .5rem; }
            .website-content-editor .ck.ck-toolbar__items { flex-wrap: wrap; gap: .125rem; }
            .website-content-editor .ck.ck-button { border-radius: .5rem; }
            .website-content-editor .ck.ck-editor__main > .ck-editor__editable { min-height: 420px; border-color: #e2e8f0; border-radius: 0 0 .75rem .75rem; padding: 1.25rem 1.5rem; color: #0f172a; box-shadow: none; }
            .website-content-editor .ck.ck-editor__main > .ck-editor__editable.ck-focused { border-color: #6366f1; box-shadow: 0 0 0 3px rgb(99 102 241 / .12); }
            .dark .website-content-editor .ck.ck-toolbar { border-color: #334155; background: #172033; }
            .dark .website-content-editor .ck.ck-editor__main > .ck-editor__editable { border-color: #334155; background: #1e293b; color: #e2e8f0; }
        </style>
    </x-slot:head>
    <div class="ds-page"><div x-cloak x-show="menuOpen" @click="menuOpen=false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div><x-admin-sidebar />
        <main class="min-w-0 lg:pl-72"><x-admin-topbar /><div class="mx-auto max-w-5xl p-5 sm:p-8">
            <div class="mb-6"><p class="text-sm font-semibold text-indigo-600">Website Settings</p><h1 class="mt-1 text-2xl font-bold">Content</h1><p class="mt-2 text-sm text-slate-500">This standalone content section appears only near the end of the storefront homepage.</p></div>
            @if(session('status'))<div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif
            <form data-ds-editable data-ds-editable-start="edit" method="POST" action="{{ route('settings.content.update') }}">@csrf @method('PUT')
                <section class="ds-card overflow-hidden"><div class="ds-card-header"><h2 class="ds-section-title">Homepage content</h2><p class="ds-section-description">Use headings, paragraphs, lists, links and quotes to create an informative homepage section.</p></div>
                    <div class="website-content-editor p-5 sm:p-7"><textarea id="footer-content" name="footer_content" data-ds-rich-text>{{ old('footer_content', $settings->footer_content) }}</textarea>@error('footer_content')<p class="ds-error">{{ $message }}</p>@enderror</div>
                    <div class="flex justify-end border-t border-slate-200 px-5 py-4 dark:border-slate-700"><button class="ds-button-primary">Save content</button></div>
                </section>
            </form>
        </div></main>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{const field=document.querySelector('#footer-content');if(field&&window.ClassicEditor){window.ClassicEditor.create(field,{toolbar:['heading','|','bold','italic','link','bulletedList','numberedList','blockQuote','undo','redo']}).then(editor=>field.closest('form')?.dispatchEvent(new CustomEvent('ds-editor-ready',{detail:{editor,control:field,wrapper:editor.ui.view.element}}))).catch(()=>{});}});</script>
</x-admin-layout>
