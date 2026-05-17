@php
    $preview = is_array($preview ?? null) ? $preview : [];
    $fields = collect($preview['fields'] ?? [])->take(3);
    $total = (int) ($preview['total'] ?? 0);
@endphp

@if(($preview['empty'] ?? false) || $fields->isEmpty())
    <div class="preview-empty-inline">
        <strong>Indikator belum tersedia</strong>
        <small>Data bidang usaha akan tampil setelah terdapat data UMKM pada wilayah ini.</small>
    </div>
@else
    @foreach($fields as $index => $field)
        @php
            $percent = max(1, min(100, (int) round((float) ($field['percent'] ?? 0))));
            $count = max($index === 0 ? 1 : 0, (int) round($total * ($percent / 100)));
            $width = max(24, min(95, $percent));
        @endphp

        <div>
            <span>
                <span class="indicator-name">{{ $field['name'] ?? 'Indikator' }}</span>
                <span class="indicator-meta">{{ number_format($count, 0, ',', '.') }} UMKM • {{ $percent }}%</span>
            </span>
            <b style="width: {{ $width }}%;"></b>
        </div>
    @endforeach
@endif
