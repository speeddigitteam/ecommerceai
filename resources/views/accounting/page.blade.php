<x-admin-layout :title="$title">
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />

            <div class="mx-auto max-w-7xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Accounting</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ $title }}</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
                </div>

                <section class="rounded-2xl border border-slate-200 bg-white p-8 text-center dark:border-slate-800 dark:bg-[#161f2e]">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 19h16M7 16v-5m5 5V5m5 11v-3"/></svg></span>
                    <h2 class="mt-4 text-lg font-bold">{{ $title }} module</h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">The navigation and page are ready. Accounting records and calculations can be added here in the next step.</p>
                </section>
            </div>
        </main>
    </div>
</x-admin-layout>
