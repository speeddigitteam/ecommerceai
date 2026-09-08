<x-admin-layout title="Newsletter">
    <div x-data="{ deletingSubscriber: null }" class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />

            <div class="mx-auto max-w-7xl p-5 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Marketing</p>
                        <h1 class="mt-1 text-2xl font-bold">Newsletter subscribers</h1>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Everyone who signed up for updates from the storefront footer.</p>
                    </div>
                    <a href="{{ route('newsletter.export') }}" class="ds-button-secondary inline-flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                        Export CSV
                    </a>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                <div class="mb-6 grid gap-4 sm:grid-cols-2">
                    <div class="ds-card p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Subscribed</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $subscribedCount }}</p>
                    </div>
                    <div class="ds-card p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Unsubscribed</p>
                        <p class="mt-2 text-2xl font-bold text-slate-500">{{ $unsubscribedCount }}</p>
                    </div>
                </div>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-[#161f2e]">
                    <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                        <h2 class="font-bold">All subscribers</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $subscribers->total() }} {{ Str::plural('subscriber', $subscribers->total()) }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Subscribed</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($subscribers as $subscriber)
                                    <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                        <td class="px-6 py-4 font-semibold">{{ $subscriber->email }}</td>
                                        <td class="px-6 py-4">
                                            @if ($subscriber->status === 'subscribed')
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Subscribed</span>
                                            @else
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">Unsubscribed</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $subscriber->subscribed_at?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex justify-end">
                                                <button type="button" @click="deletingSubscriber = @js($subscriber->only(['id', 'email']))" title="Remove subscriber" class="grid h-9 w-9 place-items-center rounded-full bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-300">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-14 text-center text-slate-500">No subscribers yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($subscribers->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $subscribers->links() }}</div>
                    @endif
                </section>
            </div>
        </main>

        <div x-cloak x-show="deletingSubscriber" x-transition.opacity @keydown.escape.window="deletingSubscriber = null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div @click.outside="deletingSubscriber = null" class="ds-card w-full max-w-md overflow-hidden shadow-2xl">
                <form method="POST" :action="'{{ url('/newsletter') }}/' + deletingSubscriber?.id">
                    @csrf
                    @method('DELETE')
                    <div class="p-6 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                        </div>
                        <h2 class="mt-4 text-xl font-bold">Remove subscriber?</h2>
                        <p class="mt-2 text-sm text-slate-500">Permanently remove <strong x-text="deletingSubscriber?.email"></strong>?</p>
                    </div>
                    <div class="flex justify-center gap-3 border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                        <button type="button" @click="deletingSubscriber = null" class="ds-button-secondary">Cancel</button>
                        <button class="ds-button-danger">Yes, remove</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
