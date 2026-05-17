@props([
    'title',
    'description' => null,
    'iconClass' => 'footer-icon',
])

<div {{ $attributes->class(['card', 'h-100', 'border-0', 'footer-feature']) }}>
    <div class="card-body">
        @isset($icon)
            <span class="{{ trim($iconClass) }}" aria-hidden="true">
                {{ $icon }}
            </span>
        @endisset

        <strong>{{ $title }}</strong>

        @if(filled($description))
            <small>{{ $description }}</small>
        @endif
    </div>
</div>