@php
    $preview = is_array($preview ?? null) ? $preview : [];
    $total = (int) ($preview['total'] ?? 0);
    $active = (int) ($preview['active'] ?? 0);
    $validation = (int) ($preview['validation'] ?? 0);
@endphp

<strong class="count-up" data-count="{{ $total }}" data-public-metric="total">{{ number_format($total, 0, ',', '.') }}</strong>
<strong class="count-up" data-count="{{ $active }}" data-public-metric="active">{{ number_format($active, 0, ',', '.') }}</strong>
<strong class="count-up" data-count="{{ $validation }}" data-public-metric="validation">{{ number_format($validation, 0, ',', '.') }}</strong>
