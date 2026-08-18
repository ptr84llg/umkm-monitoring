@php
    $contextFilters = $contextFilters ?? [];
    $contextOptions = $contextOptions ?? [];
    $contextCount = $contextCount ?? null;
    $contextSearch = trim((string)($contextSearch ?? ''));

    $resolveOptionName = static function ($items, $selectedId): ?string {
        if ($selectedId === null || $selectedId === '') {
            return null;
        }

        foreach ($items as $item) {
            $itemId = is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null);
            $itemName = is_array($item) ? ($item['name'] ?? null) : ($item->name ?? null);

            if ((string)$itemId === (string)$selectedId) {
                return $itemName !== null ? (string)$itemName : null;
            }
        }

        return null;
    };

    $districtName = $resolveOptionName($contextOptions['districts'] ?? [], $contextFilters['district_id'] ?? null);
    $villageName = $resolveOptionName($contextOptions['villages'] ?? [], $contextFilters['village_id'] ?? null);
    $categoryName = $resolveOptionName($contextOptions['categories'] ?? [], $contextFilters['category_id'] ?? null);
    $typeName = $resolveOptionName($contextOptions['types'] ?? [], $contextFilters['type_id'] ?? null);
    $marketingName = $resolveOptionName($contextOptions['marketingMethods'] ?? [], $contextFilters['marketing_method_id'] ?? null);
    $qualityName = ! empty($contextFilters['quality_status'])
        ? \Illuminate\Support\Str::headline((string)$contextFilters['quality_status'])
        : null;

    $regionTitle = $villageName
        ? 'Kelurahan ' . $villageName
        : ($districtName ? 'Kecamatan ' . $districtName : 'Seluruh Kota Lubuk Linggau');

    $regionSubtitle = $villageName && $districtName
        ? 'Kecamatan ' . $districtName
        : ($districtName ? 'Wilayah kecamatan yang dipilih' : 'Seluruh wilayah Kota Lubuk Linggau');

    $contextBadges = [];

    if ($categoryName) $contextBadges[] = 'Kategori: ' . $categoryName;
    if ($typeName) $contextBadges[] = 'Jenis: ' . $typeName;
    if ($marketingName) $contextBadges[] = 'Pemasaran: ' . $marketingName;
    if ($qualityName) $contextBadges[] = 'Kualitas Data: ' . $qualityName;
    if ($contextSearch !== '') $contextBadges[] = 'Pencarian: "' . $contextSearch . '"';
@endphp

<section class="card border shadow-sm bg-body" data-admin-dinas-active-context>
    <div class="card-body py-3 px-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill text-bg-primary">Pilihan Saat Ini</span>
                    @if($contextCount !== null)
                        <span class="small text-body-secondary">{{ number_format((int)$contextCount, 0, ',', '.') }} data</span>
                    @endif
                </div>
                <h2 class="h4 mb-1">{{ $regionTitle }}</h2>
                <p class="text-body-secondary mb-0">{{ $regionSubtitle }}</p>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                @forelse($contextBadges as $contextBadge)
                    <span class="badge rounded-pill text-bg-light border text-body">{{ $contextBadge }}</span>
                @empty
                    <span class="badge rounded-pill text-bg-light border text-body-secondary">Tanpa pilihan tambahan</span>
                @endforelse
            </div>
        </div>
    </div>
</section>
