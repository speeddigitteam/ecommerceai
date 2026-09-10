<x-admin-layout title="AI Content Settings">
<div class="ds-page"><div x-cloak x-show="menuOpen" @click="menuOpen=false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div><x-admin-sidebar />
<main class="min-w-0 lg:pl-72"><x-admin-topbar /><div class="mx-auto max-w-4xl p-5 sm:p-8">
<div class="mb-6"><p class="text-sm font-semibold text-indigo-600">Settings</p><h1 class="mt-1 text-2xl font-bold">AI content settings</h1><p class="mt-2 text-sm text-slate-500">Connect OpenAI once, then generate product and blog content from their editor pages.</p></div>
@if(session('status'))<div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('settings.ai-content.update') }}" class="space-y-6">@csrf @method('PUT')
<section class="ds-card ds-card-body">
<label class="flex items-center justify-between gap-4"><span><b>Enable AI content generator</b><span class="mt-1 block text-sm text-slate-500">Shows and activates generation tools in product and blog forms.</span></span><input type="checkbox" name="openai_enabled" value="1" @checked(old('openai_enabled',$settings->openai_enabled)) class="rounded border-slate-300 text-indigo-600"></label>
<div class="mt-6"><label for="openai_api_key" class="ds-field-label">OpenAI API key</label><input id="openai_api_key" name="openai_api_key" type="password" autocomplete="new-password" class="ds-input ds-control" placeholder="{{ $settings->openai_api_key ? 'Saved — leave blank to keep current key' : 'sk-...' }}"><p class="ds-help">Encrypted before storage. Leave blank when updating other settings.</p>@error('openai_api_key')<p class="ds-error">{{ $message }}</p>@enderror</div>
<div class="mt-5 grid gap-5 sm:grid-cols-2"><div><label class="ds-field-label">Model</label><input name="openai_model" value="{{ old('openai_model',$settings->openai_model ?: 'gpt-5.2') }}" required class="ds-input ds-control">@error('openai_model')<p class="ds-error">{{ $message }}</p>@enderror</div>
<div><label class="ds-field-label">Maximum output tokens</label><input type="number" name="openai_max_output_tokens" min="200" max="10000" value="{{ old('openai_max_output_tokens',$settings->openai_max_output_tokens ?: 2500) }}" required class="ds-input ds-control"></div>
<div><label class="ds-field-label">Default language</label><select name="openai_default_language" class="ds-select ds-control">@foreach(['Bangla','English','Bangla and English'] as $v)<option @selected(old('openai_default_language',$settings->openai_default_language ?: 'Bangla')===$v)>{{ $v }}</option>@endforeach</select></div>
<div><label class="ds-field-label">Default tone</label><select name="openai_default_tone" class="ds-select ds-control">@foreach(['Professional','Friendly','Persuasive','Luxury','Simple'] as $v)<option @selected(old('openai_default_tone',$settings->openai_default_tone ?: 'Professional')===$v)>{{ $v }}</option>@endforeach</select></div></div>
<div class="mt-6 flex justify-end"><button class="ds-button-primary">Save AI settings</button></div>
</section></form></div></main></div>
</x-admin-layout>
