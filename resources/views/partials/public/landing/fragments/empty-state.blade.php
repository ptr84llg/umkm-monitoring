@php
    $preview = is_array($preview ?? null) ? $preview : [];
    $label = trim((string) ($label ?? 'wilayah ini'));
    $message = $preview['message'] ?? 'Belum ada data agregat UMKM untuk wilayah yang dipilih.';
@endphp

<strong data-public-empty-title>Data UMKM {{ $label }} belum tersedia</strong>
<p data-public-empty-message>{{ $message }} Pilih wilayah lain atau kembali ke Kota Lubuklinggau untuk melihat preview agregat.</p>
