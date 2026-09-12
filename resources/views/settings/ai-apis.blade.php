<x-admin-layout title="AI APIs">
    <div class="ds-page" x-data="{ editing: @js(old('editing_provider')) }">
        <div x-cloak x-show="menuOpen" @click="menuOpen=false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />
        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-6xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600">Website settings</p>
                    <h1 class="mt-1 text-2xl font-bold">AI API providers</h1>
                    <p class="mt-2 text-sm text-slate-500">Configure content-generation providers and choose which one is active.</p>
                </div>
                @if(session('status'))
                    <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $errors->first() }}</div>
                @endif
                <section class="ds-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[780px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60">
                                <tr><th class="px-6 py-4">Provider</th><th class="px-6 py-4">API key</th><th class="px-6 py-4">Model</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Actions</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($providers as $provider)
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                        <td class="px-6 py-5"><p class="font-bold">{{ $provider['name'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $provider['description'] }}</p></td>
                                        <td class="px-6 py-5"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $provider['key_configured'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}"><span class="h-1.5 w-1.5 rounded-full {{ $provider['key_configured'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>{{ $provider['key_configured'] ? 'Configured' : 'Not configured' }}</span></td>
                                        <td class="px-6 py-5 font-mono text-xs text-slate-600 dark:text-slate-300">{{ $provider['model'] }}</td>
                                        <td class="px-6 py-5"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $provider['enabled'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ $provider['enabled'] ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="px-6 py-5"><div class="flex items-center justify-end gap-2"><button type="button" @click="editing='{{ $provider['id'] }}'" class="ds-button-secondary px-3 py-2 text-xs">Edit</button><form method="POST" action="{{ route('settings.ai-apis.toggle', $provider['id']) }}">@csrf @method('PATCH')<input type="hidden" name="active" value="{{ $provider['enabled'] ? 0 : 1 }}"><button type="submit" class="rounded-lg px-3 py-2 text-xs font-semibold {{ $provider['enabled'] ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">{{ $provider['enabled'] ? 'Deactivate' : 'Activate' }}</button></form></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">Only one provider can be active at a time. Activating one automatically deactivates the other.</div>
            </div>
        </main>
        @foreach($providers as $provider)
            <div x-cloak x-show="editing === '{{ $provider['id'] }}'" x-transition.opacity @keydown.escape.window="editing=null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                <div @click.outside="editing=null" class="ds-card max-h-[90vh] w-full max-w-2xl overflow-y-auto shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700"><div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">AI provider</p><h2 class="mt-1 text-xl font-bold">Edit {{ $provider['name'] }}</h2></div><x-modal-close-button @click="editing=null" /></div>
                    <form method="POST" action="{{ route('settings.ai-apis.update', $provider['id']) }}" class="p-6">
                        @csrf @method('PUT')
                        <input type="hidden" name="editing_provider" value="{{ $provider['id'] }}">
                        <div><label class="ds-field-label">API key</label><input name="api_key" type="password" autocomplete="new-password" class="ds-input ds-control" placeholder="{{ $provider['key_configured'] ? 'Saved — leave blank to keep current key' : $provider['key_placeholder'] }}"><p class="ds-help">Stored encrypted and used only by the Laravel backend.</p></div>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div><label class="ds-field-label">Model</label><input name="model" value="{{ old('editing_provider') === $provider['id'] ? old('model', $provider['model']) : $provider['model'] }}" required class="ds-input ds-control"></div>
                            <div><label class="ds-field-label">Maximum output tokens</label><input name="max_output_tokens" type="number" min="200" max="10000" value="{{ old('editing_provider') === $provider['id'] ? old('max_output_tokens', $provider['max_output_tokens']) : $provider['max_output_tokens'] }}" required class="ds-input ds-control"></div>
                            <div><label class="ds-field-label">Default language</label><select name="default_language" class="ds-select ds-control">@foreach(['Bangla','English','Bangla and English'] as $value)<option @selected((old('editing_provider') === $provider['id'] ? old('default_language') : $settings->openai_default_language) === $value)>{{ $value }}</option>@endforeach</select></div>
                            <div><label class="ds-field-label">Default tone</label><select name="default_tone" class="ds-select ds-control">@foreach(['Professional','Friendly','Persuasive','Luxury','Simple'] as $value)<option @selected((old('editing_provider') === $provider['id'] ? old('default_tone') : $settings->openai_default_tone) === $value)>{{ $value }}</option>@endforeach</select></div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3"><button type="button" @click="editing=null" class="ds-button-secondary">Cancel</button><button type="submit" class="ds-button-primary">Save provider</button></div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</x-admin-layout>
