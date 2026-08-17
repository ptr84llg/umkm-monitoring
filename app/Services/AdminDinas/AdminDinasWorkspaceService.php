<?php

namespace App\Services\AdminDinas;

use App\Models\Umkm\Umkm;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDinasWorkspaceService
{
    private const LOAN_SOURCE_QUALITY_MARKER = 'data keuangan tidak tersedia';

    public function __construct(private AdminDinasDashboardService $dashboardService)
    {
    }

    public function umkmIndex(array $filters, bool $canFinancial): array
    {
        $perPage = (int) ($filters['per_page'] ?? 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        $query = $this->baseQuery($filters)->select([
            'u.id',
            'u.umkm_code',
            'u.business_name',
            'u.status_data',
            'u.quality_status',
        ]);

        foreach (['source_system', 'source_record_id', 'source_active'] as $column) {
            if (Schema::hasColumn('umkms', $column)) {
                $query->addSelect('u.' . $column);
            }
        }

        $query->selectSub($this->districtSubquery(), 'district_name')
            ->selectSub($this->villageSubquery(), 'village_name')
            ->selectSub($this->classificationSubquery('category'), 'category_name')
            ->selectSub($this->classificationSubquery('type'), 'type_name')
            ->selectSub($this->baselineSubquery('employee_count'), 'employee_count')
            ->selectSub($this->qualityFlagCountSubquery(), 'quality_flag_count');

        if ($canFinancial) {
            foreach (['capital_amount', 'annual_sales_amount', 'loan_amount', 'loan_source'] as $column) {
                $query->selectSub($this->baselineSubquery($column), $column);
            }
        }

        return [
            'rows' => $query->orderBy('u.business_name')->orderBy('u.id')->paginate($perPage)->withQueryString(),
            'filters' => $filters,
            'filter_options' => $this->filterOptions(),
            'can_view_financial' => $canFinancial,
        ];
    }

    public function umkmDetail(Umkm $umkm, User $user): array
    {
        $umkm->load([
            'owners',
            'locations.district',
            'locations.village',
            'businessClassifications.category',
            'businessClassifications.businessType',
            'baselineProfile.marketingMethod',
            'legalities',
            'dataQualityFlags',
        ]);

        $location = $umkm->locations->sortBy('id')->first();
        $classification = $umkm->businessClassifications
            ->sortByDesc(fn ($item) => (int) $item->is_primary)
            ->first();
        $baseline = $umkm->baselineProfile;
        $owner = $umkm->owners->sortByDesc(fn ($item) => (int) $item->is_active)->first();

        $canFinancial = $user->hasPermission('umkm.sensitive.financial');
        $canContact = $user->hasPermission('umkm.sensitive.contact');
        $canLegality = $user->hasPermission('umkm.sensitive.legality');
        $canCoordinate = $user->hasPermission('umkm.sensitive.coordinate');

        $legalities = $umkm->legalities->map(function ($item) use ($canLegality): array {
            $raw = $item->getAttribute('nib_number');
            $masked = $item->getAttribute('nib_number_masked');

            return [
                'identified' => ($raw !== null && trim((string) $raw) !== '')
                    || ($masked !== null && trim((string) $masked) !== ''),
                'nib_number' => $canLegality ? $raw : null,
                'oss_risk_level' => $canLegality ? $item->getAttribute('oss_risk_level') : null,
                'status_data' => $item->getAttribute('status_data'),
            ];
        })->values()->all();

        return [
            'umkm' => [
                'id' => $umkm->id,
                'umkm_code' => $umkm->umkm_code,
                'business_name' => $umkm->business_name,
                'status_data' => $umkm->status_data,
                'quality_status' => $umkm->quality_status,
            ],
            'source' => [
                'system' => $umkm->getAttribute('source_system'),
                'record_id' => $umkm->getAttribute('source_record_id'),
                'active' => $umkm->getAttribute('source_active'),
                'first_seen_at' => $this->dateValue($umkm->getAttribute('source_first_seen_at')),
                'last_seen_at' => $this->dateValue($umkm->getAttribute('source_last_seen_at')),
                'detail_synced_at' => $this->dateValue($umkm->getAttribute('lss_detail_synced_at')),
            ],
            'classification' => [
                'category' => $classification?->category?->name,
                'type' => $classification?->businessType?->name,
            ],
            'location' => [
                'district' => $location?->district?->name,
                'village' => $location?->village?->name,
                'address_detail' => $location?->address_detail,
                'coordinate_status' => $location?->coordinate_status,
                'latitude' => $canCoordinate ? $location?->latitude : null,
                'longitude' => $canCoordinate ? $location?->longitude : null,
                'coordinate_visible' => $canCoordinate,
            ],
            'baseline' => [
                'employee_count' => $baseline?->employee_count,
                'marketing_method' => $baseline?->marketingMethod?->name,
                'status_data' => $baseline?->status_data,
            ],
            'financial' => $canFinancial ? [
                'capital_amount' => $baseline?->getAttribute('capital_amount'),
                'annual_sales_amount' => $baseline?->getAttribute('annual_sales_amount'),
                'baseline_monthly_revenue' => $baseline?->getAttribute('baseline_monthly_revenue'),
                'loan_amount' => $baseline?->getAttribute('loan_amount'),
                'loan_source' => $baseline?->getAttribute('loan_source'),
            ] : null,
            'owner' => [
                'name' => $owner?->owner_name,
                'phone' => $canContact ? $owner?->phone : null,
                'email' => $canContact ? $owner?->email : null,
                'contact_visible' => $canContact,
            ],
            'legalities' => $legalities,
            'legality_detail_visible' => $canLegality,
            'quality_flags' => $umkm->dataQualityFlags
                ->sortByDesc(fn ($item) => $item->status === 'open' ? 1 : 0)
                ->map(fn ($item) => [
                    'code' => $item->flag_code,
                    'group' => $item->flag_group,
                    'severity' => $item->severity,
                    'description' => $item->description,
                    'detected_value' => $item->detected_value,
                    'status' => $item->status,
                ])->values()->all(),
        ];
    }

    public function analyticsOverview(array $filters, bool $canFinancial): array
    {
        $base = $this->dashboardService->build($filters, $canFinancial);

        $base['filter_options'] = $this->filterOptions();
        $base['types'] = $this->typeSummary($filters);
        $base['workforce_by_district'] = $this->workforceByDistrict($filters);
        $base['marketing_methods'] = $this->marketingSummary($filters);
        $base['quality_groups'] = $this->qualityGroupSummary($filters);
        $base['legality_identified'] = $this->legalityIdentified($filters);

        return $base;
    }

    public function financialAnalyticsPage(array $filters): array
    {
        $base = $this->dashboardService->build($filters, true);
        $base['filter_options'] = $this->filterOptions();
        $base['loan_source_analysis'] = $this->loanSourceBreakdown($filters);
        $base['financial']['details'] = $this->financialDetailsPage($filters);

        return $base;
    }

    private function financialDetailsPage(array $filters)
    {
        $perPage = (int) ($filters['per_page'] ?? 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        $query = $this->baseQuery($filters)->select([
            'u.id',
            'u.umkm_code',
            'u.business_name',
            'u.quality_status',
        ]);

        $query->selectSub($this->districtSubquery(), 'district_name')
            ->selectSub($this->baselineSubquery('capital_amount'), 'capital_amount')
            ->selectSub($this->baselineSubquery('annual_sales_amount'), 'annual_sales_amount')
            ->selectSub($this->baselineSubquery('loan_amount'), 'loan_amount')
            ->selectSub($this->baselineSubquery('loan_source'), 'loan_source');

        $paginator = $query
            ->orderBy('u.business_name')
            ->orderBy('u.id')
            ->paginate($perPage, ['*'], 'financial_page')
            ->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(static fn ($row): array => (array) $row)
        );

        return $paginator;
    }

    private function baseQuery(array $filters): Builder
    {
        $query = DB::table('umkms as u')->whereIn('u.status_data', $this->operationalStatuses());

        if (Schema::hasColumn('umkms', 'deleted_at')) {
            $query->whereNull('u.deleted_at');
        }

        if (Schema::hasColumn('umkms', 'source_system') && Schema::hasColumn('umkms', 'source_active')) {
            $query->where(function (Builder $guard): void {
                $guard->whereNull('u.source_system')
                    ->orWhere('u.source_system', '<>', 'LSS')
                    ->orWhere('u.source_active', 1);
            });
        }

        if (! empty($filters['search'])) {
            $term = mb_strtolower(trim((string) $filters['search']));
            $query->where(function (Builder $search) use ($term): void {
                $search->whereRaw('LOWER(u.business_name) LIKE ?', ['%' . $term . '%'])
                    ->orWhereRaw('LOWER(u.umkm_code) LIKE ?', ['%' . $term . '%']);

                if (Schema::hasColumn('umkms', 'source_record_id')) {
                    $search->orWhereRaw('LOWER(u.source_record_id) LIKE ?', ['%' . $term . '%']);
                }
            });
        }

        if (! empty($filters['quality_status'])) {
            $query->where('u.quality_status', (string) $filters['quality_status']);
        }

        $this->applyExistsFilter($query, $filters, 'district_id', 'umkm_locations', 'district_region_id', 'fd');
        $this->applyExistsFilter($query, $filters, 'village_id', 'umkm_locations', 'village_region_id', 'fv');
        $this->applyExistsFilter($query, $filters, 'category_id', 'umkm_business_classifications', 'business_category_id', 'fc');
        $this->applyExistsFilter($query, $filters, 'type_id', 'umkm_business_classifications', 'business_type_id', 'ft');
        $this->applyExistsFilter($query, $filters, 'marketing_method_id', 'umkm_baseline_profiles', 'marketing_method_id', 'fm');

        return $query;
    }

    private function applyExistsFilter(
        Builder $query,
        array $filters,
        string $filterKey,
        string $table,
        string $column,
        string $alias
    ): void {
        if (empty($filters[$filterKey]) || ! Schema::hasTable($table)) {
            return;
        }

        $value = (int) $filters[$filterKey];

        $query->whereExists(function (Builder $sub) use ($value, $table, $column, $alias): void {
            $sub->selectRaw('1')
                ->from($table . ' as ' . $alias)
                ->whereColumn($alias . '.umkm_id', 'u.id')
                ->where($alias . '.' . $column, $value);

            if ($table === 'umkm_locations' && Schema::hasColumn('umkm_locations', 'deleted_at')) {
                $sub->whereNull($alias . '.deleted_at');
            }
        });
    }

    private function baseIds(array $filters): Builder
    {
        return $this->baseQuery($filters)->select('u.id');
    }

    private function filterOptions(): array
    {
        $dashboard = $this->dashboardService->build([], false);
        $options = $dashboard['filter_options'] ?? [];
        $options['qualityStatuses'] = DB::table('umkms')
            ->whereNotNull('quality_status')
            ->whereRaw("TRIM(quality_status) <> ''")
            ->distinct()
            ->orderBy('quality_status')
            ->pluck('quality_status');

        return $options;
    }

    private function districtSubquery(): Builder
    {
        $query = DB::table('umkm_locations as l')
            ->join('regions as r', 'r.id', '=', 'l.district_region_id')
            ->whereColumn('l.umkm_id', 'u.id')
            ->select('r.name')->orderBy('l.id')->limit(1);

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query;
    }

    private function villageSubquery(): Builder
    {
        $query = DB::table('umkm_locations as l')
            ->join('regions as r', 'r.id', '=', 'l.village_region_id')
            ->whereColumn('l.umkm_id', 'u.id')
            ->select('r.name')->orderBy('l.id')->limit(1);

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query;
    }

    private function classificationSubquery(string $kind): Builder
    {
        $referenceTable = $kind === 'category'
            ? 'business_category_references'
            : 'business_type_references';
        $foreignKey = $kind === 'category'
            ? 'business_category_id'
            : 'business_type_id';

        return DB::table('umkm_business_classifications as c')
            ->join($referenceTable . ' as r', 'r.id', '=', 'c.' . $foreignKey)
            ->whereColumn('c.umkm_id', 'u.id')
            ->select('r.name')
            ->orderByDesc('c.is_primary')
            ->orderBy('c.id')
            ->limit(1);
    }

    private function baselineSubquery(string $column): Builder
    {
        return DB::table('umkm_baseline_profiles as b')
            ->whereColumn('b.umkm_id', 'u.id')
            ->select('b.' . $column)
            ->orderBy('b.id')
            ->limit(1);
    }

    private function qualityFlagCountSubquery(): Builder
    {
        $query = DB::table('umkm_data_quality_flags as q')
            ->whereColumn('q.umkm_id', 'u.id')
            ->selectRaw('COUNT(*)');

        if (Schema::hasColumn('umkm_data_quality_flags', 'status')) {
            $query->where('q.status', 'open');
        }

        return $query;
    }

    private function primaryLocationSubquery(): Builder
    {
        $query = DB::table('umkm_locations')
            ->selectRaw('umkm_id, MIN(district_region_id) AS district_region_id')
            ->whereNotNull('district_region_id')
            ->groupBy('umkm_id');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    private function typeSummary(array $filters): array
    {
        return DB::table('umkm_business_classifications as c')
            ->join('business_type_references as r', 'r.id', '=', 'c.business_type_id')
            ->whereIn('c.umkm_id', $this->baseIds($filters))
            ->selectRaw('r.id, r.name, COUNT(DISTINCT c.umkm_id) AS total_umkm')
            ->groupBy('r.id', 'r.name')->orderByDesc('total_umkm')->limit(12)->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name, 'total_umkm' => (int) $row->total_umkm])
            ->all();
    }

    private function workforceByDistrict(array $filters): array
    {
        return DB::query()
            ->fromSub($this->primaryLocationSubquery(), 'pl')
            ->join('regions as r', 'r.id', '=', 'pl.district_region_id')
            ->join('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'pl.umkm_id')
            ->whereIn('pl.umkm_id', $this->baseIds($filters))
            ->selectRaw('r.id, r.name, COALESCE(SUM(b.employee_count), 0) AS total_workers')
            ->groupBy('r.id', 'r.name')->orderByDesc('total_workers')->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name, 'total_workers' => (int) $row->total_workers])
            ->all();
    }

    private function marketingSummary(array $filters): array
    {
        return DB::table('umkm_baseline_profiles as b')
            ->join('marketing_method_references as r', 'r.id', '=', 'b.marketing_method_id')
            ->whereIn('b.umkm_id', $this->baseIds($filters))
            ->selectRaw('r.id, r.name, COUNT(DISTINCT b.umkm_id) AS total_umkm')
            ->groupBy('r.id', 'r.name')->orderByDesc('total_umkm')->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name, 'total_umkm' => (int) $row->total_umkm])
            ->all();
    }

    private function qualityGroupSummary(array $filters): array
    {
        $query = DB::table('umkm_data_quality_flags as q')->whereIn('q.umkm_id', $this->baseIds($filters));

        if (Schema::hasColumn('umkm_data_quality_flags', 'status')) {
            $query->where('q.status', 'open');
        }

        return $query
            ->selectRaw("COALESCE(NULLIF(TRIM(q.flag_group), ''), 'Belum dikelompokkan') AS name, COUNT(*) AS flag_count, COUNT(DISTINCT q.umkm_id) AS affected_umkm")
            ->groupByRaw("COALESCE(NULLIF(TRIM(q.flag_group), ''), 'Belum dikelompokkan')")
            ->orderByDesc('affected_umkm')->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'flag_count' => (int) $row->flag_count, 'affected_umkm' => (int) $row->affected_umkm])
            ->all();
    }

    private function legalityIdentified(array $filters): int
    {
        $query = DB::table('umkm_legalities as l')->whereIn('l.umkm_id', $this->baseIds($filters));

        $hasRaw = Schema::hasColumn('umkm_legalities', 'nib_number');
        $hasMasked = Schema::hasColumn('umkm_legalities', 'nib_number_masked');

        $query->where(function (Builder $identified) use ($hasRaw, $hasMasked): void {
            if ($hasRaw) {
                $identified->where(function (Builder $raw): void {
                    $raw->whereNotNull('l.nib_number')->whereRaw("TRIM(l.nib_number) <> ''");
                });
            }

            if ($hasMasked) {
                $method = $hasRaw ? 'orWhere' : 'where';
                $identified->{$method}(function (Builder $masked): void {
                    $masked->whereNotNull('l.nib_number_masked')->whereRaw("TRIM(l.nib_number_masked) <> ''");
                });
            }
        });

        return (int) $query->distinct()->count('l.umkm_id');
    }

    private function loanSourceBreakdown(array $filters): array
    {
        $base = DB::table('umkm_baseline_profiles as b')->whereIn('b.umkm_id', $this->baseIds($filters));

        $missing = (int) (clone $base)
            ->where(function (Builder $q): void {
                $q->whereNull('b.loan_source')->orWhereRaw("TRIM(b.loan_source) = ''");
            })->distinct()->count('b.umkm_id');

        $identified = (clone $base)
            ->whereNotNull('b.loan_source')
            ->whereRaw("TRIM(b.loan_source) <> ''")
            ->whereRaw('LOWER(b.loan_source) NOT LIKE ?', ['%' . self::LOAN_SOURCE_QUALITY_MARKER . '%'])
            ->selectRaw('b.loan_source AS raw_value, COUNT(DISTINCT b.umkm_id) AS total_umkm')
            ->groupBy('b.loan_source')->orderByDesc('total_umkm')->get()
            ->map(fn ($row) => ['raw_value' => (string) $row->raw_value, 'total_umkm' => (int) $row->total_umkm])
            ->all();

        $issues = (clone $base)
            ->whereNotNull('b.loan_source')
            ->whereRaw('LOWER(b.loan_source) LIKE ?', ['%' . self::LOAN_SOURCE_QUALITY_MARKER . '%'])
            ->selectRaw('b.loan_source AS raw_value, COUNT(DISTINCT b.umkm_id) AS total_umkm')
            ->groupBy('b.loan_source')->orderByDesc('total_umkm')->get()
            ->map(fn ($row) => ['raw_value' => (string) $row->raw_value, 'total_umkm' => (int) $row->total_umkm])
            ->all();

        return ['identified' => $identified, 'quality_issues' => $issues, 'missing' => $missing];
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    private function operationalStatuses(): array
    {
        $statuses = array_values(array_filter(array_map(
            fn ($status) => trim((string) $status),
            (array) config('umkm.data.operational_statuses', ['resmi', 'terbatas'])
        )));

        return $statuses === [] ? ['resmi', 'terbatas'] : $statuses;
    }
}
