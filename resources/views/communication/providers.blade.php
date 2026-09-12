<x-admin-layout :title="ucfirst($channel).' Providers'">
    <div
        x-data="{ editing: null, remove: null, showPassword: false }"
        class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100"
    >
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />

            <div class="mx-auto max-w-7xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Settings / Communication</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ ucfirst($channel) }} Providers</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Connect, test and control the outbound {{ $channel }} service.</p>
                </div>

                @if (session('status'))
                    <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">{{ $errors->first() }}</div>
                @endif

                <div class="grid items-start gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#161f2e] xl:sticky xl:top-24">
                        <div class="border-b border-slate-200 bg-indigo-50/60 px-5 py-4 dark:border-slate-800 dark:bg-indigo-500/10">
                            <p class="text-xs font-bold uppercase tracking-[.14em] text-indigo-600 dark:text-indigo-400" x-text="editing ? 'Update outbound route' : 'New outbound route'"></p>
                            <h2 class="mt-1 text-xl font-bold" x-text="editing ? 'Edit {{ ucfirst($channel) }} Provider' : 'Add {{ ucfirst($channel) }} Provider'"></h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Credentials are encrypted before storage.</p>
                        </div>

                        <form method="POST" :action="editing ? '{{ url('/communication/providers/'.$channel) }}/' + editing.id : '{{ route('communication.providers.store', $channel) }}'">
                            @csrf
                            <input x-show="editing" :disabled="!editing" type="hidden" name="_method" value="PUT">
                            <input type="hidden" name="driver" value="{{ $channel === 'email' ? 'smtp' : 'http' }}">

                            <div class="space-y-5 p-5">
                                <fieldset>
                                    <legend class="text-xs font-bold uppercase tracking-wider text-slate-400">Identity</legend>
                                    <label class="mt-3 block text-sm font-semibold">Provider name <span class="text-rose-500">*</span>
                                        <input name="name" required :value="editing?.name || ''" placeholder="{{ $channel === 'email' ? 'Google Workspace SMTP' : 'SMS gateway' }}" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">
                                    </label>
                                </fieldset>

                                @if ($channel === 'email')
                                    <fieldset class="space-y-3">
                                        <legend class="text-xs font-bold uppercase tracking-wider text-slate-400">Connection</legend>
                                        <label class="block text-sm font-semibold">SMTP host <span class="text-rose-500">*</span><input name="host" required :value="editing?.settings?.host || ''" placeholder="smtp.gmail.com" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                        <div class="grid grid-cols-[1fr_110px] gap-3">
                                            <label class="text-sm font-semibold">Encryption<select name="encryption" :value="editing?.settings?.encryption || 'tls'" class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"><option value="tls">TLS</option><option value="ssl">SSL</option><option value="none">None</option></select></label>
                                            <label class="text-sm font-semibold">Port <span class="text-rose-500">*</span><input name="port" type="number" required :value="editing?.settings?.port || 587" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                        </div>
                                    </fieldset>
                                    <fieldset class="space-y-3">
                                        <legend class="text-xs font-bold uppercase tracking-wider text-slate-400">Authentication</legend>
                                        <label class="block text-sm font-semibold">Username<input name="username" :value="editing?.settings?.username || ''" autocomplete="off" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                        <label class="block text-sm font-semibold">Password
                                            <span class="relative mt-2 block"><input name="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="Leave blank to keep saved password" class="w-full rounded-xl border-slate-300 px-3 py-2.5 pr-11 text-sm dark:border-slate-700 dark:bg-slate-800"><button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-slate-500" x-text="showPassword ? 'Hide' : 'Show'"></button></span>
                                        </label>
                                    </fieldset>
                                    <fieldset class="space-y-3">
                                        <legend class="text-xs font-bold uppercase tracking-wider text-slate-400">Sender identity</legend>
                                        <label class="block text-sm font-semibold">Sender email <span class="text-rose-500">*</span><input name="from_address" type="email" required :value="editing?.settings?.from_address || ''" placeholder="hello@example.com" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                        <label class="block text-sm font-semibold">Sender name <span class="text-rose-500">*</span><input name="from_name" required :value="editing?.settings?.from_name || ''" placeholder="Your Store" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                    </fieldset>
                                @else
                                    <fieldset class="space-y-3">
                                        <legend class="text-xs font-bold uppercase tracking-wider text-slate-400">API connection</legend>
                                        <label class="block text-sm font-semibold">API URL <span class="text-rose-500">*</span><input name="url" type="url" required :value="editing?.settings?.url || ''" placeholder="https://sms.example.com/send" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                        <label class="block text-sm font-semibold">API key<input name="api_key" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="Leave blank to keep saved API key" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                        <label class="block text-sm font-semibold">Sender ID<input name="sender_id" :value="editing?.settings?.sender_id || ''" placeholder="YourStore" class="mt-2 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                        <div class="grid grid-cols-2 gap-3"><label class="text-sm font-semibold">Receiver field <span class="text-rose-500">*</span><input name="to_parameter" required :value="editing?.settings?.to_parameter || 'to'" class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"></label><label class="text-sm font-semibold">Message field <span class="text-rose-500">*</span><input name="message_parameter" required :value="editing?.settings?.message_parameter || 'message'" class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"></label></div>
                                        <label class="block text-sm font-semibold">API key field<input name="api_key_parameter" :value="editing?.settings?.api_key_parameter || 'api_key'" class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"></label>
                                    </fieldset>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-3 border-t border-slate-200 p-5 dark:border-slate-800">
                                <button type="button" @click="editing = null; showPassword = false; $el.closest('form').reset()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Reset</button>
                                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700" x-text="editing ? 'Save changes' : 'Save provider'"></button>
                            </div>
                        </form>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#161f2e]">
                        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                            <div><h2 class="text-lg font-bold">{{ ucfirst($channel) }} Providers</h2><p class="mt-1 text-xs text-slate-500">Showing {{ $providers->firstItem() ?? 0 }}–{{ $providers->lastItem() ?? 0 }} of {{ $providers->total() }} entries</p></div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:bg-slate-800">{{ $providers->where('is_active', true)->count() }} active</div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60"><tr><th class="px-5 py-4">Provider</th><th class="px-5 py-4">Connection</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Test connection</th><th class="px-5 py-4 text-right">Actions</th></tr></thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($providers as $provider)
                                        @php($editableProvider = ['id' => $provider->id, 'name' => $provider->name, 'settings' => collect($provider->settings)->except(['password', 'api_key'])->all()])
                                        <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                            <td class="px-5 py-4"><p class="font-semibold">{{ $provider->name }}</p><p class="mt-1 text-xs uppercase text-slate-400">{{ $provider->driver }}</p></td>
                                            <td class="max-w-[230px] px-5 py-4"><p class="truncate text-slate-600 dark:text-slate-300">{{ $channel === 'email' ? ($provider->settings['host'] ?? 'Not configured') : ($provider->settings['url'] ?? 'Not configured') }}</p><p class="mt-1 text-xs text-slate-400">Credentials ••••••••</p></td>
                                            <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $provider->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}"><span class="h-1.5 w-1.5 rounded-full {{ $provider->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>{{ $provider->is_active ? 'Active' : 'Inactive' }}</span></td>
                                            <td class="px-5 py-4"><form method="POST" action="{{ route('communication.providers.test', [$channel, $provider]) }}" class="flex gap-2">@csrf<input required name="test_recipient" placeholder="{{ $channel === 'email' ? 'name@example.com' : '01XXXXXXXXX' }}" class="w-40 rounded-lg border-slate-300 px-2.5 py-2 text-xs dark:border-slate-700 dark:bg-slate-800"><button class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-300">Test</button></form></td>
                                            <td class="px-5 py-4"><div class="flex justify-end gap-2"><button @click="editing = {{ Illuminate\Support\Js::from($editableProvider) }}; window.scrollTo({ top: 0, behavior: 'smooth' })" class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-300">Edit</button><form method="POST" action="{{ route('communication.providers.toggle', [$channel, $provider]) }}">@csrf @method('PATCH')<button class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">{{ $provider->is_active ? 'Disable' : 'Enable' }}</button></form><button @click="remove = {{ $provider->id }}" class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-300">Delete</button></div></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-6 py-20 text-center"><span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600 dark:bg-indigo-500/15">✉</span><p class="mt-4 font-bold">No providers connected yet</p><p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Complete the form on the left to register your first outbound {{ $channel }} route.</p></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $providers->links() }}</div>
                    </section>
                </div>
            </div>
        </main>

        <div x-cloak x-show="remove" x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4">
            <div @click.outside="remove = null" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl dark:bg-[#161f2e]"><h3 class="text-lg font-bold">Delete provider?</h3><p class="mt-2 text-sm text-slate-500">This connection will be removed permanently.</p><form method="POST" :action="'{{ url('/communication/providers/'.$channel) }}/' + remove" class="mt-6 flex justify-end gap-3">@csrf @method('DELETE')<button type="button" @click="remove = null" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">Cancel</button><button class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white">Delete</button></form></div>
        </div>
    </div>
</x-admin-layout>
