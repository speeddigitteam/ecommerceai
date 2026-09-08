<x-admin-layout title="Analytics">
    <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-7xl p-5 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Insights</p>
                        <h1 class="mt-1 text-2xl font-bold">Analytics</h1>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Storefront traffic from Google Analytics — last 28 days.</p>
                    </div>
                    <a href="{{ route('settings.analytics.edit') }}" class="ds-button-secondary inline-flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M19.4 15a2 2 0 0 0 .4 2.2l-2.2 2.2A2 2 0 0 0 15 19.4a2 2 0 0 0-1.2 1.8h-3.6A2 2 0 0 0 9 19.4a2 2 0 0 0-2.2.4l-2.2-2.2A2 2 0 0 0 5 15a2 2 0 0 0-1.8-1.2v-3.6A2 2 0 0 0 5 9a2 2 0 0 0-.4-2.2l2.2-2.2A2 2 0 0 0 9 5a2 2 0 0 0 1.2-1.8h3.6A2 2 0 0 0 15 5a2 2 0 0 0 2.2-.4l2.2 2.2A2 2 0 0 0 19 9a2 2 0 0 0 1.8 1.2v3.6A2 2 0 0 0 19.4 15Z"/></svg>
                        Settings
                    </a>
                </div>

                @if (! $configured)
                    <section class="ds-card ds-card-body text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 19h16M7 16v-5m5 5V5m5 11v-3"/></svg>
                        </div>
                        <h2 class="mt-4 text-lg font-bold">Connect Google Analytics</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">Add a GA4 property ID and a service account key in settings to see visitor charts here.</p>
                        <a href="{{ route('settings.analytics.edit') }}" class="ds-button-primary mt-5 inline-flex px-5">Go to Analytics settings</a>
                    </section>
                @elseif ($error)
                    <section class="ds-card ds-card-body">
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                            </span>
                            <div>
                                <h2 class="font-bold">Could not load Analytics data</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $error }}</p>
                                <p class="mt-3 text-sm">Check the Property ID and that the service account has Viewer access on this GA4 property in <a href="{{ route('settings.analytics.edit') }}" class="font-semibold text-indigo-600 dark:text-indigo-400">Analytics settings</a>.</p>
                            </div>
                        </div>
                    </section>
                @else
                    @php
                        $totals = $summary['totals'];
                        $trend = $summary['trend'];
                        $topPages = $summary['topPages'];
                        $tiles = [
                            ['label' => 'Active users', 'value' => number_format($totals['activeUsers']), 'color' => 'indigo'],
                            ['label' => 'New users', 'value' => number_format($totals['newUsers']), 'color' => 'sky'],
                            ['label' => 'Sessions', 'value' => number_format($totals['sessions']), 'color' => 'emerald'],
                            ['label' => 'Page views', 'value' => number_format($totals['screenPageViews']), 'color' => 'amber'],
                            ['label' => 'Avg. session', 'value' => gmdate('i:s', (int) $totals['averageSessionDuration']), 'color' => 'violet'],
                            ['label' => 'Bounce rate', 'value' => round($totals['bounceRate'] * 100, 1).'%', 'color' => 'rose'],
                        ];
                        $sessionsValues = array_column($trend, 'sessions');
                        $maxSessions = max(1, ...($sessionsValues ?: [0]));
                        $lastIndex = max(0, count($trend) - 1);
                        $points = collect($trend)->values()->map(fn (array $point, int $index): array => [
                            'x' => $lastIndex === 0 ? 0 : round(($index / $lastIndex) * 100, 2),
                            'y' => round(100 - (($point['sessions'] / $maxSessions) * 100), 2),
                            'date' => $point['date'],
                            'sessions' => $point['sessions'],
                        ]);
                        $polyline = $points->map(fn (array $p): string => $p['x'].','.$p['y'])->implode(' ');
                    @endphp

                    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($tiles as $tile)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-[#161f2e]">
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $tile['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold">{{ $tile['value'] }}</p>
                            </div>
                        @endforeach
                    </section>

                    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-[#161f2e] sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="font-bold">Sessions</h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Daily, last 28 days</p>
                            </div>
                            @if ($trend !== [])
                                <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format(end($trend)['sessions']) }} <span class="font-normal text-slate-400">today</span></p>
                            @endif
                        </div>

                        @if ($trend === [])
                            <p class="mt-8 py-10 text-center text-sm text-slate-500">No session data yet for this period.</p>
                        @else
                            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="mt-6 h-40 w-full overflow-visible">
                                <polyline fill="none" stroke="#4f46e5" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" points="{{ $polyline }}" />
                                @foreach ($points as $point)
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="1.4" fill="#4f46e5" vector-effect="non-scaling-stroke"><title>{{ $point['date'] }}: {{ number_format($point['sessions']) }} sessions</title></circle>
                                @endforeach
                            </svg>
                            <div class="mt-2 flex justify-between text-xs text-slate-400">
                                <span>{{ $trend[0]['date'] }}</span>
                                <span>{{ end($trend)['date'] }}</span>
                            </div>
                        @endif
                    </section>

                    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-[#161f2e]">
                        <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                            <h2 class="font-bold">Top pages</h2>
                            <p class="mt-1 text-xs text-slate-500">By page views, last 28 days</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[420px] text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                    <tr>
                                        <th class="px-6 py-4">Page</th>
                                        <th class="px-6 py-4 text-right">Views</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($topPages as $page)
                                        <tr>
                                            <td class="px-6 py-4 font-mono text-xs">{{ $page['path'] }}</td>
                                            <td class="px-6 py-4 text-right font-semibold">{{ number_format($page['views']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="px-6 py-14 text-center text-slate-500">No page view data yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>
        </main>
    </div>
</x-admin-layout>
