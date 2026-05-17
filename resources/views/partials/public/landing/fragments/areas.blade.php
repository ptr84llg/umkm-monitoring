@php
    $preview = is_array($preview ?? null) ? $preview : [];
    $areas = collect($preview['areas'] ?? [])->take(3);
@endphp

@if(($preview['empty'] ?? false) || $areas->isEmpty())
    <div class="preview-empty-inline">
        <strong>Data wilayah belum tersedia</strong>
        <small>Belum ada ringkasan UMKM publik untuk wilayah yang dipilih.</small>
    </div>
@else
    @foreach($areas as $area)
        @php
            $count = (int) ($area['count'] ?? 0);
            $percent = max(0, min(100, (int) round((float) ($area['percent'] ?? 0))));
        @endphp

        <div>
            <span>{{ $area['name'] ?? 'Wilayah' }}</span>
            <strong>{{ number_format($count, 0, ',', '.') }} UMKM</strong>
            <small>{{ $area['sector'] ?? 'Indikator' }} {{ $percent }}%</small>
        </div>
    @endforeach
@endif
