@props([
    'iconClass' => '',
])

<span {{ $attributes->class(['umkm-section-pill', 'landing-pill']) }}>
    @isset($icon)
        <span class="{{ trim($iconClass) }}" aria-hidden="true">
            {{ $icon }}
        </span>
    @endisset

    <span>{{ $slot }}</span>
</span>