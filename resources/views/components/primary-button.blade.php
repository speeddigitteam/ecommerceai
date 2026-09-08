<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ds-button-primary']) }}>
    {{ $slot }}
</button>
