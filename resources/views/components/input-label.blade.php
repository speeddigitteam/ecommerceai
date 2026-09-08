@props(['value'])

<label {{ $attributes->merge(['class' => 'ds-field-label']) }}>
    {{ $value ?? $slot }}
</label>
