<?php

namespace App\Support\PublicLanding;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PublicLandingAdvancedAnalytics
{
    public static function workforce(array $context, Builder $base, int $total, array $filters = []): array
    {
        if (! self::hasBaseline('employee_count')) {
            return [
                'total_umkm' => $total,
                'total_workers' => 0,
                'raw_total_workers' => 0,
                'avg_workers' => 0,
                'median_workers' => 0,
                'filled_total' => 0,
                'valid_filled_total' => 0,
                'excluded_total' => 0,
                'sanity_limit' => self::employeeSanityLimit(),
                'buckets' => [],
                'top_sectors' => [],
            ];
        }

        $limit = self::employeeSanityLimit();
        $joined = self::cloneQuery($base)
            ->leftJoin('umkm_baseline_profiles as workforce_baseline', 'workforce_baseline.umkm_id', '=', 'umkms.id');

        $filled = self::countDistinct(self::cloneQuery($joined)->whereNotNull('workforce_baseline.employee_count'));
        $validBase = self::cloneQuery($joined)
            ->whereNotNull('workforce_baseline.employee_count')
            ->whereBetween('workforce_baseline.employee_count', [0, $limit]);
        $validFilled = self::countDistinct($validBase);
        $excluded = self::countDistinct(
            self::cloneQuery($joined)
                ->whereNotNull('workforce_baseline.employee_count')
                ->where('workforce_baseline.employee_count', '>', $limit)
        );

        $rawTotalWorkers = (int) self::cloneQuery($joined)->sum(DB::raw('COALESCE(workforce_baseline.employee_count, 0)'));
        $validTotalWorkers = (int) self::cloneQuery($validBase)->sum(DB::raw('COALESCE(workforce_baseline.employee_count, 0)'));
        $medianWorkers = self::medianEmployeeCount($validBase);

        return [
            'total_umkm' => $total,
            'total_workers' => $validTotalWorkers,
            'raw_total_workers' => $rawTotalWorkers,
            'avg_workers' => $validFilled > 0 ? round($validTotalWorkers / $validFilled, 2) : 0,
            'median_workers' => $medianWorkers,
            'filled_total' => $filled,
            'valid_filled_total' => $validFilled,
            'excluded_total' => $excluded,
            'sanity_limit' => $limit,
            'buckets' => self::employeeBuckets($joined, $validFilled, $limit),
            'top_sectors' => self::workerTopSectors($base, $limit),
        ];
    }

    public static function economy(array $context, Builder $base, int $total, array $filters = []): array
    {
        if (! Schema::hasTable('umkm_baseline_profiles') || ! Schema::hasColumn('umkm_baseline_profiles', 'umkm_id')) {
            return [
                'total_umkm' => $total,
                'capital_filled' => 0,
                'annual_sales_filled' => 0,
                'monthly_revenue_filled' => 0,
                'loan_amount_filled' => 0,
                'loan_source_filled' => 0,
                'capital_buckets' => [],
                'annual_sales_buckets' => [],
                'loan_sources' => [],
            ];
        }

        $joined = self::cloneQuery($base)
            ->leftJoin('umkm_baseline_profiles as economy_baseline', 'economy_baseline.umkm_id', '=', 'umkms.id');

        return [
            'total_umkm' => $total,
            'capital_filled' => self::hasBaseline('capital_amount') ? self::countDistinct(self::cloneQuery($joined)->whereNotNull('economy_baseline.capital_amount')) : 0,
            'annual_sales_filled' => self::hasBaseline('annual_sales_amount') ? self::countDistinct(self::cloneQuery($joined)->whereNotNull('economy_baseline.annual_sales_amount')) : 0,
            'monthly_revenue_filled' => self::hasBaseline('baseline_monthly_revenue') ? self::countDistinct(self::cloneQuery($joined)->whereNotNull('economy_baseline.baseline_monthly_revenue')) : 0,
            'loan_amount_filled' => self::hasBaseline('loan_amount') ? self::countDistinct(self::cloneQuery($joined)->whereNotNull('economy_baseline.loan_amount')) : 0,
            'loan_source_filled' => self::hasBaseline('loan_source') ? self::countDistinct(self::cloneQuery($joined)->whereNotNull('economy_baseline.loan_source')) : 0,
            'capital_buckets' => self::hasBaseline('capital_amount') ? self::moneyBuckets($joined, 'capital_amount', $total) : [],
            'annual_sales_buckets' => self::hasBaseline('annual_sales_amount') ? self::moneyBuckets($joined, 'annual_sales_amount', $total) : [],
            'loan_sources' => self::hasBaseline('loan_source') ? self::loanSourceRows($joined, $total) : [],
        ];
    }

    public static function marketAccess(array $context, Builder $base, int $total, array $filters = []): array
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_category_references')
            || ! Schema::hasTable('umkm_baseline_profiles')
            || ! Schema::hasTable('marketing_method_references')
            || ! Schema::hasColumn('umkm_business_classifications', 'umkm_id')
            || ! Schema::hasColumn('umkm_business_classifications', 'business_category_id')
            || ! Schema::hasColumn('umkm_baseline_profiles', 'umkm_id')
            || ! Schema::hasColumn('umkm_baseline_profiles', 'marketing_method_id')
        ) {
            return [
                'total_umkm' => $total,
                'categories' => [],
                'category_count' => 0,
                'method_names' => [],
                'method_count' => 0,
                'method_coverage_total' => 0,
            ];
        }

        $query = self::cloneQuery($base)
            ->join('umkm_business_classifications as market_classifications', 'market_classifications.umkm_id', '=', 'umkms.id')
            ->join('business_category_references as market_categories', 'market_categories.id', '=', 'market_classifications.business_category_id')
            ->leftJoin('umkm_baseline_profiles as market_baseline', 'market_baseline.umkm_id', '=', 'umkms.id')
            ->leftJoin('marketing_method_references as market_methods', 'market_methods.id', '=', 'market_baseline.marketing_method_id');

        self::applyClassificationGuards($query, 'market_classifications');
        self::applyReferenceActiveGuard($query, 'business_category_references', 'market_categories');
        self::applyReferenceActiveGuard($query, 'marketing_method_references', 'market_methods');

        $rawRows = $query
            ->select(
                'market_categories.id as category_id',
                'market_categories.name as category_name',
                DB::raw("COALESCE(market_methods.name, 'Belum tersedia') as method_name"),
                DB::raw('COUNT(DISTINCT umkms.id) as total_count')
            )
            ->groupBy(
                'market_categories.id',
                'market_categories.name',
                DB::raw("COALESCE(market_methods.name, 'Belum tersedia')")
            )
            ->orderByDesc('total_count')
            ->get();

        $categories = [];
        $methodNames = [];

        foreach ($rawRows as $row) {
            $categoryId = (int) $row->category_id;
            $methodGroup = self::normalizeMarketingMethod((string) ($row->method_name ?: 'Belum tersedia'));
            $count = (int) $row->total_count;

            if (! isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'name' => (string) ($row->category_name ?: 'Belum tersedia'),
                    'total_umkm' => 0,
                    'methods' => [],
                ];
            }

            $categories[$categoryId]['total_umkm'] += $count;
            $categories[$categoryId]['methods'][$methodGroup] = ($categories[$categoryId]['methods'][$methodGroup] ?? 0) + $count;

            if (! in_array($methodGroup, $methodNames, true)) {
                $methodNames[] = $methodGroup;
            }
        }

        $preferredOrder = ['Konvensional', 'Digital', 'Both', 'Belum tersedia', 'Lainnya'];
        usort($methodNames, static function (string $a, string $b) use ($preferredOrder): int {
            $aIndex = array_search($a, $preferredOrder, true);
            $bIndex = array_search($b, $preferredOrder, true);
            $aIndex = $aIndex === false ? 99 : $aIndex;
            $bIndex = $bIndex === false ? 99 : $bIndex;

            return $aIndex <=> $bIndex ?: strcmp($a, $b);
        });

        $categoryCount = count($categories);
        $methodCount = count(array_filter(
            $methodNames,
            static fn (string $name): bool => $name !== 'Belum tersedia'
        ));

        $methodCoverageQuery = self::cloneQuery($base)
            ->join('umkm_baseline_profiles as market_coverage_baseline', 'market_coverage_baseline.umkm_id', '=', 'umkms.id')
            ->join('marketing_method_references as market_coverage_methods', 'market_coverage_methods.id', '=', 'market_coverage_baseline.marketing_method_id');
        self::applyReferenceActiveGuard($methodCoverageQuery, 'marketing_method_references', 'market_coverage_methods');
        $methodCoverageTotal = self::countDistinct($methodCoverageQuery);

        $rows = collect($categories)
            ->map(function (array $category) use ($methodNames): array {
                $totalCategory = max(0, (int) $category['total_umkm']);
                $methods = [];

                foreach ($methodNames as $methodName) {
                    $count = (int) ($category['methods'][$methodName] ?? 0);
                    $methods[] = [
                        'name' => $methodName,
                        'total' => $count,
                        'percentage' => self::percent($count, $totalCategory),
                    ];
                }

                $digitalTotal = collect($methods)
                    ->filter(fn (array $method): bool => in_array($method['name'], ['Digital', 'Both'], true))
                    ->sum('total');

                return [
                    'id' => (int) $category['id'],
                    'name' => (string) $category['name'],
                    'total_umkm' => $totalCategory,
                    'digital_total' => (int) $digitalTotal,
                    'digital_percentage' => self::percent((int) $digitalTotal, $totalCategory),
                    'methods' => $methods,
                ];
            })
            ->sortByDesc('total_umkm')
            ->take(10)
            ->values()
            ->all();

        return [
            'total_umkm' => $total,
            'categories' => $rows,
            'category_count' => $categoryCount,
            'method_names' => array_values($methodNames),
            'method_count' => $methodCount,
            'method_coverage_total' => $methodCoverageTotal,
        ];
    }

    public static function legality(array $context, Builder $base, int $total, array $filters = []): array
    {
        if (! Schema::hasTable('umkm_legalities') || ! Schema::hasColumn('umkm_legalities', 'umkm_id')) {
            return [
                'total_umkm' => $total,
                'legalities_total' => 0,
                'legalities_percentage' => 0,
                'nib_identified_total' => 0,
                'unidentified_total' => $total,
                'stages' => [
                    ['name' => 'Total UMKM', 'total' => $total, 'percentage' => 100.0],
                    ['name' => 'Legalitas terdata', 'total' => 0, 'percentage' => 0.0],
                ],
            ];
        }

        $joined = self::cloneQuery($base)
            ->leftJoin('umkm_legalities as legality_records', 'legality_records.umkm_id', '=', 'umkms.id');
        $legalitiesTotal = self::countDistinct(self::cloneQuery($joined)->whereNotNull('legality_records.umkm_id'));

        $hasRawNib = Schema::hasColumn('umkm_legalities', 'nib_number');
        $hasMaskedNib = Schema::hasColumn('umkm_legalities', 'nib_number_masked');
        $nibIdentifiedTotal = 0;

        if ($hasRawNib || $hasMaskedNib) {
            $nibQuery = self::cloneQuery($joined)->where(function (Builder $query) use ($hasRawNib, $hasMaskedNib): void {
                if ($hasRawNib) {
                    $query->whereNotNull('legality_records.nib_number');
                }

                if ($hasMaskedNib) {
                    $method = $hasRawNib ? 'orWhereNotNull' : 'whereNotNull';
                    $query->{$method}('legality_records.nib_number_masked');
                }
            });

            $nibIdentifiedTotal = self::countDistinct($nibQuery);
        }

        return [
            'total_umkm' => $total,
            'legalities_total' => $legalitiesTotal,
            'legalities_percentage' => self::percent($legalitiesTotal, $total),
            'nib_identified_total' => $nibIdentifiedTotal,
            'unidentified_total' => max(0, $total - $legalitiesTotal),
            'stages' => [
                ['name' => 'Total UMKM', 'total' => $total, 'percentage' => 100.0],
                ['name' => 'Legalitas terdata', 'total' => $legalitiesTotal, 'percentage' => self::percent($legalitiesTotal, $total)],
                ['name' => 'NIB teridentifikasi', 'total' => $nibIdentifiedTotal, 'percentage' => self::percent($nibIdentifiedTotal, $total)],
            ],
        ];
    }

    private static function employeeBuckets(Builder $joined, int $validTotal, int $limit): array
    {
        return [
            self::bucket('0 pekerja', self::countDistinct(self::cloneQuery($joined)->where('workforce_baseline.employee_count', 0)), $validTotal),
            self::bucket('1 pekerja', self::countDistinct(self::cloneQuery($joined)->where('workforce_baseline.employee_count', 1)), $validTotal),
            self::bucket('2–3 pekerja', self::countDistinct(self::cloneQuery($joined)->whereBetween('workforce_baseline.employee_count', [2, 3])), $validTotal),
            self::bucket('4–5 pekerja', self::countDistinct(self::cloneQuery($joined)->whereBetween('workforce_baseline.employee_count', [4, 5])), $validTotal),
            self::bucket('6–10 pekerja', self::countDistinct(self::cloneQuery($joined)->whereBetween('workforce_baseline.employee_count', [6, 10])), $validTotal),
            self::bucket('11–50 pekerja', self::countDistinct(self::cloneQuery($joined)->whereBetween('workforce_baseline.employee_count', [11, 50])), $validTotal),
            self::bucket('> 50 pekerja', self::countDistinct(self::cloneQuery($joined)->whereBetween('workforce_baseline.employee_count', [51, $limit])), $validTotal),
        ];
    }

    private static function workerTopSectors(Builder $base, int $limit): array
    {
        if (! self::hasBaseline('employee_count') || ! Schema::hasTable('umkm_business_classifications') || ! Schema::hasTable('business_type_references')) {
            return [];
        }

        $query = self::cloneQuery($base)
            ->join('umkm_baseline_profiles as worker_sector_baseline', 'worker_sector_baseline.umkm_id', '=', 'umkms.id')
            ->join('umkm_business_classifications as worker_sector_classifications', 'worker_sector_classifications.umkm_id', '=', 'umkms.id')
            ->join('business_type_references as worker_sector_types', 'worker_sector_types.id', '=', 'worker_sector_classifications.business_type_id')
            ->whereBetween('worker_sector_baseline.employee_count', [0, $limit]);

        self::applyClassificationGuards($query, 'worker_sector_classifications');
        self::applyReferenceActiveGuard($query, 'business_type_references', 'worker_sector_types');

        return $query
            ->select(
                'worker_sector_types.name',
                DB::raw('SUM(COALESCE(worker_sector_baseline.employee_count, 0)) as total_workers'),
                DB::raw('COUNT(DISTINCT umkms.id) as total_umkm')
            )
            ->groupBy('worker_sector_types.name')
            ->havingRaw('SUM(COALESCE(worker_sector_baseline.employee_count, 0)) > 0')
            ->orderByDesc('total_workers')
            ->limit(10)
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) ($row->name ?: 'Belum tersedia'),
                'total_workers' => (int) $row->total_workers,
                'total_umkm' => (int) $row->total_umkm,
            ])
            ->values()
            ->all();
    }

    private static function medianEmployeeCount(Builder $validBase): float
    {
        $values = self::cloneQuery($validBase)
            ->select('umkms.id', DB::raw('workforce_baseline.employee_count as employee_count'))
            ->distinct()
            ->orderBy('workforce_baseline.employee_count')
            ->pluck('employee_count')
            ->map(static fn ($value): int => (int) $value)
            ->values();

        $count = $values->count();
        if ($count < 1) {
            return 0.0;
        }

        $middle = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return round(((float) $values[$middle - 1] + (float) $values[$middle]) / 2, 2);
    }

    private static function employeeSanityLimit(): int
    {
        return max(10, (int) config('umkm.public_analytics.employee_count_sanity_limit', 1000));
    }

    private static function moneyBuckets(Builder $joined, string $column, int $total): array
    {
        $field = 'economy_baseline.' . $column;

        return [
            self::bucket('Belum tersedia', self::countDistinct(self::cloneQuery($joined)->whereNull($field)), $total),
            self::bucket('< Rp1 juta', self::countDistinct(self::cloneQuery($joined)->whereNotNull($field)->where($field, '<', 1000000)), $total),
            self::bucket('Rp1–5 juta', self::countDistinct(self::cloneQuery($joined)->whereBetween($field, [1000000, 5000000])), $total),
            self::bucket('Rp5–10 juta', self::countDistinct(self::cloneQuery($joined)->where($field, '>', 5000000)->where($field, '<=', 10000000)), $total),
            self::bucket('Rp10–50 juta', self::countDistinct(self::cloneQuery($joined)->where($field, '>', 10000000)->where($field, '<=', 50000000)), $total),
            self::bucket('> Rp50 juta', self::countDistinct(self::cloneQuery($joined)->where($field, '>', 50000000)), $total),
        ];
    }

    private static function loanSourceRows(Builder $joined, int $total): array
    {
        return self::cloneQuery($joined)
            ->select(
                DB::raw("COALESCE(NULLIF(TRIM(economy_baseline.loan_source), ''), 'Belum tersedia') as name"),
                DB::raw('COUNT(DISTINCT umkms.id) as total_count')
            )
            ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(economy_baseline.loan_source), ''), 'Belum tersedia')"))
            ->orderByDesc('total_count')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) ($row->name ?: 'Belum tersedia'),
                'total' => (int) $row->total_count,
                'percentage' => self::percent((int) $row->total_count, $total),
            ])
            ->values()
            ->all();
    }

    private static function normalizeMarketingMethod(string $method): string
    {
        $normalized = mb_strtolower(trim($method));

        if ($normalized === '' || $normalized === 'belum tersedia') {
            return 'Belum tersedia';
        }

        if (
            str_contains($normalized, 'both')
            || str_contains($normalized, 'campuran')
            || str_contains($normalized, 'kombinasi')
            || str_contains($normalized, 'hybrid')
        ) {
            return 'Both';
        }

        if (
            str_contains($normalized, 'digital')
            || str_contains($normalized, 'online')
            || str_contains($normalized, 'marketplace')
            || str_contains($normalized, 'media sosial')
            || str_contains($normalized, 'website')
        ) {
            return 'Digital';
        }

        if (
            str_contains($normalized, 'konvensional')
            || str_contains($normalized, 'offline')
            || str_contains($normalized, 'tradisional')
        ) {
            return 'Konvensional';
        }

        return 'Lainnya';
    }

    private static function bucket(string $name, int $count, int $total): array
    {
        return ['name' => $name, 'total' => $count, 'percentage' => self::percent($count, $total)];
    }

    private static function hasBaseline(string $column): bool
    {
        return Schema::hasTable('umkm_baseline_profiles')
            && Schema::hasColumn('umkm_baseline_profiles', 'umkm_id')
            && Schema::hasColumn('umkm_baseline_profiles', $column);
    }

    private static function countDistinct(Builder $query): int
    {
        return (int) self::cloneQuery($query)->distinct()->count('umkms.id');
    }

    private static function cloneQuery(Builder $query): Builder
    {
        return clone $query;
    }

    private static function percent(int $part, int $whole): float
    {
        return $whole > 0 ? round(($part / $whole) * 100, 2) : 0.0;
    }

    private static function applyClassificationGuards(Builder $query, string $alias): void
    {
        if (Schema::hasColumn('umkm_business_classifications', 'is_primary')) {
            $query->where($alias . '.is_primary', true);
        }

        if (Schema::hasColumn('umkm_business_classifications', 'status_data')) {
            $query->whereIn($alias . '.status_data', ['resmi', 'terbatas']);
        }
    }

    private static function applyReferenceActiveGuard(Builder $query, string $table, string $alias): void
    {
        if (Schema::hasColumn($table, 'is_active')) {
            $query->where(function (Builder $guard) use ($alias): void {
                $guard->where($alias . '.is_active', true)->orWhereNull($alias . '.is_active');
            });
        }
    }
}
