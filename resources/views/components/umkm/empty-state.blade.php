@props([
    'iconClass' => '',
])

<div {{ $attributes->class(['umkm-empty-state']) }}>
    @isset($icon)
        <span class="{{ trim('umkm-empty-icon '.$iconClass) }}" aria-hidden="true">
            {{ $icon }}
        </span>
    @endisset

    <div>
        {{ $slot }}
    </div>
</div>