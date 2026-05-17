@props([
    'title',
    'iconClass' => 'footer-column-icon',
])

<div {{ $attributes->class(['card', 'h-100', 'border-0', 'footer-column']) }}>
    <div class="card-body">
        <div class="footer-column-title d-flex align-items-center gap-3 mb-3">
            @isset($icon)
                <span class="{{ trim($iconClass) }}" aria-hidden="true">
                    {{ $icon }}
                </span>
            @endisset

            <strong>{{ $title }}</strong>
        </div>

        {{ $slot }}
    </div>
</div>