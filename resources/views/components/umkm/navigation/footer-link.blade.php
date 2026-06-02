@props([
    'href' => '#',
    'iconClass' => 'footer-link-icon',
])

<a {{ $attributes->class(['footer-link'])->merge(['href' => $href]) }}>
    @isset($icon)
        <span class="{{ trim($iconClass) }}" aria-hidden="true">
            {{ $icon }}
        </span>
    @endisset

    <span>{{ $slot }}</span>
</a>