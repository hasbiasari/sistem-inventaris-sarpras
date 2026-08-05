<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-app-primary']) }}>
    {{ $slot }}
</button>