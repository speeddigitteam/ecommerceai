<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ds-button-danger']) }}>
    {{ $slot }}
</button>
