<x-admin-layout title="Backup Settings">
    <div x-data="{ deletingBackup: null, disk: '{{ old('disk', $settings->backup_disk) }}' }" class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-4xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Website Settings</p>
                    <h1 class="mt-1 text-2xl font-bold">Backup</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Choose where database backups are stored and run a backup on demand.</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">{{ session('error') }}</div>
                @endif

                <form method="POST" action="{{ route('settings.backup.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <section class="ds-card ds-card-body">
                        <h2 class="ds-section-title">Storage destination</h2>
                        <p class="ds-section-description">Backups always run on demand from the button below. Choose where the resulting file is saved.</p>

                        <div class="mt-6 space-y-5">
                            <div>
                                <label for="backup-disk" class="ds-field-label">Destination</label>
                                <select id="backup-disk" name="disk" x-model="disk" class="ds-input ds-control">
                                    <option value="local" @selected(old('disk', $settings->backup_disk) === 'local')>Local server storage</option>
                                    <option value="s3" @selected(old('disk', $settings->backup_disk) === 's3')>Amazon S3 (or S3-compatible)</option>
                                </select>
                                @error('disk')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="backup-frequency" class="ds-field-label">Automatic backup</label>
                                <select id="backup-frequency" name="frequency" class="ds-input ds-control">
                                    <option value="off" @selected(old('frequency', $settings->backup_frequency) === 'off')>Off — manual only</option>
                                    <option value="daily" @selected(old('frequency', $settings->backup_frequency) === 'daily')>Daily at 2:00 AM</option>
                                    <option value="weekly" @selected(old('frequency', $settings->backup_frequency) === 'weekly')>Weekly — Sunday at 2:00 AM</option>
                                    <option value="monthly" @selected(old('frequency', $settings->backup_frequency) === 'monthly')>Monthly — 1st day at 2:00 AM</option>
                                </select>
                                <p class="ds-help">
                                    Requires the Laravel scheduler cron entry on the server: <code class="font-mono">* * * * * php artisan schedule:run</code>.
                                    @if ($settings->backup_last_run_at)
                                        Last backup: {{ $settings->backup_last_run_at->format('M d, Y H:i') }}.
                                    @else
                                        No backup has run yet.
                                    @endif
                                </p>
                                @error('frequency')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="backup-path-prefix" class="ds-field-label">Folder name (optional)</label>
                                <input id="backup-path-prefix" name="path_prefix" value="{{ old('path_prefix', $settings->backup_path_prefix) }}" autocomplete="off" class="ds-input ds-control font-mono" placeholder="{{ config('backup.backup.name') }}">
                                <p class="ds-help">The folder under which backup files are grouped on the chosen disk. Leave blank to use the default.</p>
                                @error('path_prefix')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>

                            <div x-cloak x-show="disk === 's3'" class="space-y-5 border-t border-slate-200 pt-5 dark:border-slate-700">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label for="backup-s3-bucket" class="ds-field-label">Bucket</label>
                                        <input id="backup-s3-bucket" name="s3_bucket" value="{{ old('s3_bucket', $settings->backup_s3_bucket) }}" autocomplete="off" class="ds-input ds-control font-mono">
                                        @error('s3_bucket')<p class="ds-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="backup-s3-region" class="ds-field-label">Region</label>
                                        <input id="backup-s3-region" name="s3_region" value="{{ old('s3_region', $settings->backup_s3_region) }}" autocomplete="off" class="ds-input ds-control font-mono" placeholder="us-east-1">
                                        @error('s3_region')<p class="ds-error">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                <div>
                                    <label for="backup-s3-key" class="ds-field-label">Access Key ID</label>
                                    <input id="backup-s3-key" name="s3_key" value="{{ old('s3_key', $settings->backup_s3_key) }}" autocomplete="off" class="ds-input ds-control font-mono">
                                    @error('s3_key')<p class="ds-error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="backup-s3-secret" class="ds-field-label">Secret Access Key</label>
                                    <input id="backup-s3-secret" name="s3_secret" type="password" autocomplete="new-password" class="ds-input ds-control font-mono" placeholder="{{ filled($settings->backup_s3_secret) ? 'Configured — leave blank to keep it' : 'Paste your S3 secret access key' }}">
                                    <p class="ds-help">Stored encrypted and never shown again.</p>
                                    @error('s3_secret')<p class="ds-error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="backup-s3-endpoint" class="ds-field-label">Custom endpoint (optional)</label>
                                    <input id="backup-s3-endpoint" name="s3_endpoint" value="{{ old('s3_endpoint', $settings->backup_s3_endpoint) }}" autocomplete="off" class="ds-input ds-control font-mono" placeholder="https://nyc3.digitaloceanspaces.com">
                                    <p class="ds-help">Only needed for S3-compatible providers such as DigitalOcean Spaces, Cloudflare R2, or MinIO.</p>
                                    @error('s3_endpoint')<p class="ds-error">{{ $message }}</p>@enderror
                                </div>
                                <label class="flex items-center gap-2 text-sm font-semibold">
                                    <input type="hidden" name="s3_use_path_style_endpoint" value="0">
                                    <input type="checkbox" name="s3_use_path_style_endpoint" value="1" @checked(old('s3_use_path_style_endpoint', $settings->backup_s3_use_path_style_endpoint)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    Use path-style endpoint
                                </label>
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <button class="ds-button-primary px-5">Save backup settings</button>
                    </div>
                </form>

                <section class="ds-card ds-card-body mt-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="ds-section-title">Run backup now</h2>
                            <p class="ds-section-description">Creates a database backup and stores it on the <strong>{{ $activeDisk }}</strong> disk using the settings above.</p>
                        </div>
                        <form method="POST" action="{{ route('settings.backup.run') }}">
                            @csrf
                            <button class="ds-button-primary whitespace-nowrap px-5">Run backup now</button>
                        </form>
                    </div>
                </section>

                <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-[#161f2e]">
                    <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                        <h2 class="font-bold">Existing backups</h2>
                        <p class="mt-1 text-xs text-slate-500">Stored on the <strong>{{ $activeDisk }}</strong> disk.</p>
                    </div>

                    @if ($listError)
                        <div class="p-5 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $listError }}</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[560px] text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                    <tr>
                                        <th class="px-6 py-4">File</th>
                                        <th class="px-6 py-4">Size</th>
                                        <th class="px-6 py-4">Created</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($backups as $backup)
                                        <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                            <td class="px-6 py-4 font-mono text-xs">{{ $backup['name'] }}</td>
                                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Number::fileSize($backup['size']) }}</td>
                                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $backup['modified_at']->format('M d, Y H:i') }}</td>
                                            <td class="px-6 py-4">
                                                <div class="flex justify-end gap-2">
                                                    <a href="{{ route('settings.backup.download', ['path' => $backup['path']]) }}" title="Download" class="grid h-9 w-9 place-items-center rounded-full bg-indigo-50 text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-300">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                                                    </a>
                                                    <button type="button" @click="deletingBackup = @js($backup['path'])" title="Delete" class="grid h-9 w-9 place-items-center rounded-full bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-300">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-6 py-14 text-center text-slate-500">No backups yet. Run a backup to create one.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
        </main>

        <div x-cloak x-show="deletingBackup" x-transition.opacity @keydown.escape.window="deletingBackup = null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div @click.outside="deletingBackup = null" class="ds-card w-full max-w-md overflow-hidden shadow-2xl">
                <form method="POST" action="{{ route('settings.backup.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="path" :value="deletingBackup">
                    <div class="p-6 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                        </div>
                        <h2 class="mt-4 text-xl font-bold">Delete this backup?</h2>
                        <p class="mt-2 text-sm text-slate-500">This permanently removes <strong x-text="deletingBackup"></strong>.</p>
                    </div>
                    <div class="flex justify-center gap-3 border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                        <button type="button" @click="deletingBackup = null" class="ds-button-secondary">Cancel</button>
                        <button class="ds-button-danger">Yes, delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
