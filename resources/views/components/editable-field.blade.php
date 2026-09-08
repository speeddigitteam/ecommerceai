@props([
    'label',
    'name',
    'value' => '',
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'emptyText' => 'Not provided',
])

<div>
    <label for="editable-{{ $name }}" class="ds-field-label">{{ $label }}</label>
    <div x-show="!editing" class="ds-readonly-value">{{ filled($value) ? $value : $emptyText }}</div>

    @if ($type === 'textarea')
        <textarea x-cloak x-show="editing" :disabled="!editing || submitting" data-editable-control id="editable-{{ $name }}" name="{{ $name }}" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->class(['ds-textarea ds-control']) }}>{{ old($name, $value) }}</textarea>
    @else
        <input x-cloak x-show="editing" :disabled="!editing || submitting" data-editable-control id="editable-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->class(['ds-input ds-control']) }}>
    @endif

    @error($name)<p class="ds-error">{{ $message }}</p>@enderror
</div>
