<x-admin-layout title="FAQ Settings">
    <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-5xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Website Settings</p>
                    <h1 class="mt-1 text-2xl font-bold">Homepage FAQs</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Add, remove and reorder the questions shown in the storefront FAQ section.</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('settings.faq.update') }}" x-data="{
                    faqs: @js(old('faqs', $faqs)),
                    add() { this.faqs.push({ question: '', answer: '' }); this.$nextTick(() => this.$refs.list.lastElementChild?.querySelector('input')?.focus()) },
                    remove(index) { this.faqs.splice(index, 1) },
                    move(index, offset) { const target = index + offset; if (target < 0 || target >= this.faqs.length) return; const item = this.faqs.splice(index, 1)[0]; this.faqs.splice(target, 0, item) }
                }" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <section class="ds-card ds-card-body">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="ds-section-title">Questions and answers</h2>
                                <p class="ds-section-description">Up to 20 FAQs. Their saved order is used on the homepage and in FAQ structured data.</p>
                            </div>
                            <button type="button" @click="add()" class="ds-button-secondary shrink-0">+ Add FAQ</button>
                        </div>

                        <div x-ref="list" class="mt-6 space-y-4">
                            <template x-for="(faq, index) in faqs" :key="index">
                                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700 sm:p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="text-sm font-bold">FAQ <span x-text="index + 1"></span></h3>
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button type="button" @click="move(index, -1)" :disabled="index === 0" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold disabled:opacity-40 dark:border-slate-700">Up</button>
                                            <button type="button" @click="move(index, 1)" :disabled="index === faqs.length - 1" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold disabled:opacity-40 dark:border-slate-700">Down</button>
                                            <button type="button" @click="remove(index)" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">Remove</button>
                                        </div>
                                    </div>
                                    <div class="mt-4 grid gap-4">
                                        <div>
                                            <label :for="`faq-question-${index}`" class="ds-field-label">Question</label>
                                            <input :id="`faq-question-${index}`" :name="`faqs[${index}][question]`" x-model="faq.question" required maxlength="255" class="ds-input ds-control" placeholder="Enter a customer question">
                                        </div>
                                        <div>
                                            <label :for="`faq-answer-${index}`" class="ds-field-label">Answer</label>
                                            <textarea :id="`faq-answer-${index}`" :name="`faqs[${index}][answer]`" x-model="faq.answer" required maxlength="2000" rows="4" class="ds-textarea ds-control" placeholder="Write a clear answer"></textarea>
                                        </div>
                                    </div>
                                </article>
                            </template>
                            <div x-show="faqs.length === 0" class="rounded-2xl border border-dashed border-slate-300 px-5 py-10 text-center dark:border-slate-700">
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No FAQs configured.</p>
                                <button type="button" @click="add()" class="mt-3 text-sm font-bold text-indigo-600 dark:text-indigo-400">+ Add your first FAQ</button>
                            </div>
                        </div>

                        @error('faqs')<p class="ds-error">{{ $message }}</p>@enderror
                        @error('faqs.*.question')<p class="ds-error">{{ $message }}</p>@enderror
                        @error('faqs.*.answer')<p class="ds-error">{{ $message }}</p>@enderror
                    </section>

                    <div class="flex justify-end">
                        <button class="ds-button-primary px-5">Save FAQs</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-admin-layout>