@props([
    'label',
    'name',
    'value' => '',
    'options' => [],
    'required' => false,
    'placeholder' => 'Select an option',
    'emptyText' => 'Not selected',
])

@php
    $selectedValue = old($name, $value);
    $displayValue = $options[$value] ?? $emptyText;
@endphp

<div>
    <label for="editable-{{ $name }}" class="ds-field-label">{{ $label }}</label>
    <div x-show="!editing" class="ds-readonly-value">{{ $displayValue }}</div>
    <select x-cloak x-show="editing" :disabled="!editing || submitting" data-editable-control id="editable-{{ $name }}" name="{{ $name }}" @required($required) {{ $attributes->class(['ds-select ds-control']) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selectedValue === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)<p class="ds-error">{{ $message }}</p>@enderror
</div>
