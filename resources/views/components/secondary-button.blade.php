<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-app-secondary']) }}>
    {{ $slot }}
</button>