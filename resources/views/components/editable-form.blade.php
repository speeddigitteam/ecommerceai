@props([
    'action',
    'method' => 'POST',
    'title' => 'Details',
    'description' => null,
    'startEditing' => false,
    'submitLabel' => 'Save changes',
])

@php
    $httpMethod = strtoupper($method);
    $formMethod = in_array($httpMethod, ['GET', 'POST'], true) ? $httpMethod : 'POST';
@endphp

<section x-data="editableForm(@js($startEditing))" class="ds-card overflow-hidden">
    <form x-ref="form" method="{{ $formMethod }}" action="{{ $action }}" @input="trackChanges" @change="trackChanges" @editable-form-change="trackChanges" @submit="submit">
        @csrf
        @if (! in_array($httpMethod, ['GET', 'POST'], true))
            @method($httpMethod)
        @endif

        <header class="ds-card-header flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="ds-section-title">{{ $title }}</h2>
                    <span x-cloak x-show="editing" class="ds-edit-mode-badge">Editing</span>
                </div>
                @if ($description)
                    <p class="ds-section-description">{{ $description }}</p>
                @endif
            </div>
            <button x-show="!editing" type="button" @click="beginEdit" class="ds-button-secondary shrink-0" aria-label="Edit {{ $title }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.9 3.1 4 4L7 21H3v-4L16.9 3.1Z"/></svg>
                Edit
            </button>
        </header>

        <div class="ds-card-body">
            {{ $slot }}
        </div>

        <footer x-cloak x-show="editing" class="flex flex-col-reverse gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <p class="text-xs font-medium" :class="dirty ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'" x-text="dirty ? 'You have unsaved changes.' : 'Change a field to enable saving.'"></p>
            <div class="flex justify-end gap-3">
                <button type="button" @click="cancelEdit" :disabled="submitting" class="ds-button-secondary">Cancel</button>
                <button type="submit" :disabled="!dirty || submitting" class="ds-button-primary">
                    <svg x-cloak x-show="submitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                    <span x-text="submitting ? 'Saving...' : @js($submitLabel)"></span>
                </button>
            </div>
        </footer>
    </form>
</section>
