@props([
    'title' => 'Catatan:',
])

<div {{ $attributes->class(['umkm-public-note', 'landing-public-note']) }}>
    @if(filled($title))
        <strong>{{ $title }}</strong>
    @endif

    <span>{{ $slot }}</span>
</div>