<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-app-danger']) }}>
    {{ $slot }}
</button>