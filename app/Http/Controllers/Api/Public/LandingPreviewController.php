<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LandingPreviewController extends Controller
{
    public function data(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'scope' => ['nullable', 'string', Rule::in(['city', 'district', 'village'])],
            'mode' => ['nullable', 'string', Rule::in(['kinerja', 'wilayah', 'legalitas'])],
            'label' => ['nullable', 'string', 'max:120'],
            'province_code' => ['nullable', 'string', 'max:20'],
            'city_code' => ['nullable', 'string', 'max:20'],
            'district_code' => ['nullable', 'string', 'max:20'],
            'village_code' => ['nullable', 'string', 'max:20'],
            'has_public_umkm_data' => ['nullable', Rule::in(['1', '0', 'true', 'false', 'unknown', 'null'])],
        ]);

        if ($validator->fails()) {
            return response()
                ->json([
                    'ok' => false,
                    'message' => 'Parameter preview publik tidak valid.',
                    'data' => [
                        'reason' => 'invalid_preview_parameter',
                    ],
                ], 422)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        }

        $input = $validator->validated();

        $scope = $input['scope'] ?? 'city';
        $mode = $input['mode'] ?? 'kinerja';
        $label = $this->cleanLabel($input['label'] ?? config('umkm.landing_region.city_name', 'Kota Lubuklinggau'));
        $hasPublicData = $this->normalizePublicDataFlag($input['has_public_umkm_data'] ?? null);
        $regionCode = $this->regionCode($input);

        $preview = $this->makePreview($scope, $regionCode, $label, $hasPublicData);
        $chart = $this->makeChart($mode, $scope, $regionCode, $label, $preview);

        return response()
            ->json([
                'ok' => true,
                'message' => 'Preview publik berhasil dimuat.',
                'data' => [
                    'selection' => [
                        'scope' => $scope,
                        'label' => $label,
                        'region_code' => $regionCode,
                        'has_public_umkm_data' => $hasPublicData,
                    ],
                    'preview' => $preview,
                    'chart' => $chart,
                ],
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function cleanLabel(string $value): string
    {
        $label = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?: '');

        if ($label === '') {
            return (string) config('umkm.landing_region.city_name', 'Kota Lubuklinggau');
        }

        return str_replace(['Sumber data:', 'Sumber Data:'], '', $label);
    }

    private function normalizePublicDataFlag(mixed $value): ?bool
    {
        if ($value === null || $value === '' || $value === 'unknown' || $value === 'null') {
            return null;
        }

        if ($value === true || $value === '1' || $value === 'true') {
            return true;
        }

        if ($value === false || $value === '0' || $value === 'false') {
            return false;
        }

        return null;
    }

    private function regionCode(array $input): string
    {
        return (string) (
            $input['village_code']
            ?? $input['district_code']
            ?? $input['city_code']
            ?? config('umkm.landing_region.city_code', '16.73')
        );
    }

    private function hashValue(string $input): int
    {
        return abs((int) crc32($input));
    }

    private function makePreview(string $scope, string $regionCode, string $label, ?bool $hasPublicData): array
    {
        $seed = $this->hashValue($regionCode . ':' . $scope);

        if ($hasPublicData === false) {
            return [
                'empty' => true,
                'total' => 0,
                'active' => 0,
                'validation' => 0,
                'watched' => 'Belum tersedia',
                'dominant' => 'Belum tersedia',
                'fields' => [],
                'areas' => [],
                'message' => 'Belum ada data agregat UMKM untuk wilayah ini.',
            ];
        }

        if ($scope === 'city') {
            $total = 1248;
            $active = 1086;
            $validation = 36;
            $fields = [
                ['name' => 'Perdagangan', 'percent' => 82],
                ['name' => 'Kuliner', 'percent' => 74],
                ['name' => 'Jasa', 'percent' => 64],
            ];
            $areas = [
                ['name' => 'Lubuk Linggau Timur II', 'count' => 312, 'sector' => 'Perdagangan', 'percent' => 42],
                ['name' => 'Lubuk Linggau Utara II', 'count' => 286, 'sector' => 'Kuliner', 'percent' => 35],
                ['name' => 'Lubuk Linggau Barat II', 'count' => 214, 'sector' => 'Jasa', 'percent' => 23],
            ];

            return [
                'empty' => false,
                'total' => $total,
                'active' => $active,
                'validation' => $validation,
                'watched' => '8 Kecamatan',
                'dominant' => 'Perdagangan',
                'fields' => $fields,
                'areas' => $areas,
                'message' => null,
            ];
        }

        if ($scope === 'district') {
            $total = 110 + ($seed % 115);
            $active = (int) round($total * (0.81 + (($seed % 8) / 100)));
            $validation = 3 + ($seed % 7);
            $dominantOptions = ['Perdagangan', 'Kuliner', 'Jasa', 'Industri Rumah Tangga'];
            $dominant = $dominantOptions[$seed % count($dominantOptions)];
            $fields = [
                ['name' => $dominant, 'percent' => 76 + ($seed % 12)],
                ['name' => $dominant === 'Kuliner' ? 'Perdagangan' : 'Kuliner', 'percent' => 66 + ($seed % 10)],
                ['name' => 'Jasa', 'percent' => 56 + ($seed % 9)],
            ];

            return [
                'empty' => false,
                'total' => $total,
                'active' => $active,
                'validation' => $validation,
                'watched' => 'Semua Kelurahan',
                'dominant' => $dominant,
                'fields' => $fields,
                'areas' => $this->makeAreaStats($label, $total, $fields, $seed),
                'message' => null,
            ];
        }

        $total = 18 + ($seed % 54);
        $active = (int) round($total * (0.78 + (($seed % 10) / 100)));
        $validation = 1 + ($seed % 4);
        $dominantOptions = ['Perdagangan', 'Kuliner', 'Jasa'];
        $dominant = $dominantOptions[$seed % count($dominantOptions)];
        $fields = [
            ['name' => $dominant, 'percent' => 70 + ($seed % 16)],
            ['name' => $dominant === 'Perdagangan' ? 'Kuliner' : 'Perdagangan', 'percent' => 58 + ($seed % 14)],
            ['name' => 'Jasa', 'percent' => 48 + ($seed % 12)],
        ];

        return [
            'empty' => false,
            'total' => $total,
            'active' => $active,
            'validation' => $validation,
            'watched' => '1 Kelurahan',
            'dominant' => $dominant,
            'fields' => $fields,
            'areas' => [
                [
                    'name' => $label,
                    'count' => $total,
                    'sector' => $dominant,
                    'percent' => $fields[0]['percent'],
                ],
            ],
            'message' => null,
        ];
    }

    private function makeAreaStats(string $label, int $total, array $fields, int $seed): array
    {
        $baseName = trim(str_replace(['Kecamatan ', 'Kelurahan ', 'Desa '], '', $label));
        $names = [
            $baseName . ' Area I',
            $baseName . ' Area II',
            $baseName . ' Area III',
        ];
        $ratios = [0.42, 0.34, 0.24];

        return collect($names)->map(function (string $name, int $index) use ($total, $fields, $ratios, $seed) {
            $field = $fields[$index % count($fields)];

            return [
                'name' => $name,
                'count' => max(1, (int) round($total * $ratios[$index])),
                'sector' => $field['name'],
                'percent' => max(1, min(100, (int) $field['percent'] - ($index * 3) + ($seed % 3))),
            ];
        })->values()->all();
    }

    private function makeChart(string $mode, string $scope, string $regionCode, string $label, array $preview): array
    {
        $seed = $this->hashValue($regionCode . ':' . $scope . ':' . $mode);

        if (($preview['empty'] ?? false) === true) {
            return [
                'mode' => $mode,
                'title' => 'Data UMKM ' . $label . ' belum tersedia',
                'subtitle' => 'Belum ada data agregat publik untuk wilayah yang dipilih',
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                'unit_label' => 'Jumlah UMKM',
                'percent_label' => 'Persentase (%)',
                'unit_data' => [0, 0, 0, 0, 0, 0, 0],
                'percent_data' => [0, 0, 0, 0, 0, 0, 0],
                'summary_one' => 'Wilayah tanpa data agregat',
                'summary_two' => 'Grafik belum tersedia',
                'summary_three' => 'Pilih wilayah lain',
            ];
        }

        if ($mode === 'wilayah') {
            return [
                'mode' => $mode,
                'title' => 'Sebaran UMKM ' . $label,
                'subtitle' => 'Preview distribusi agregat berdasarkan wilayah terpilih',
                'labels' => collect($preview['areas'] ?? [])->pluck('name')->values()->all() ?: ['Wilayah I', 'Wilayah II', 'Wilayah III'],
                'unit_label' => 'Jumlah UMKM',
                'percent_label' => 'Konsentrasi (%)',
                'unit_data' => collect($preview['areas'] ?? [])->pluck('count')->values()->all() ?: [18 + ($seed % 56), 24 + ($seed % 48), 16 + ($seed % 36)],
                'percent_data' => collect($preview['areas'] ?? [])->pluck('percent')->values()->all() ?: [8 + ($seed % 18), 12 + ($seed % 16), 10 + ($seed % 14)],
                'summary_one' => $scope === 'city' ? 'Kecamatan terpantau' : 'Wilayah terpantau',
                'summary_two' => 'Jumlah dan konsentrasi wilayah',
                'summary_three' => 'Sebaran ' . $label,
            ];
        }

        if ($mode === 'legalitas') {
            $factor = max(0.2, ((int) ($preview['total'] ?? 0)) / 180);

            return [
                'mode' => $mode,
                'title' => 'Legalitas dan Kelengkapan Data ' . $label,
                'subtitle' => 'Preview rasio legalitas dan kelengkapan data UMKM',
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                'unit_label' => 'UMKM berlegalitas',
                'percent_label' => 'Kelengkapan data (%)',
                'unit_data' => collect([44, 51, 58, 66, 74, 82, 91])->map(fn ($value) => max(4, (int) round($value * $factor)))->values()->all(),
                'percent_data' => collect([46, 52, 58, 63, 69, 74, 79])->map(fn ($value) => min(92, $value + ($seed % 7)))->values()->all(),
                'summary_one' => 'Legalitas, profil, lokasi',
                'summary_two' => 'Jumlah dan rasio kelengkapan',
                'summary_three' => 'Kesiapan data monitoring',
            ];
        }

        $factor = max(0.2, ((int) ($preview['active'] ?? 0)) / 160);

        return [
            'mode' => 'kinerja',
            'title' => 'Kinerja UMKM ' . $label,
            'subtitle' => 'Preview agregat perkembangan UMKM aktif pada wilayah terpilih',
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
            'unit_label' => 'UMKM aktif',
            'percent_label' => 'Pertumbuhan kinerja (%)',
            'unit_data' => collect([62, 69, 76, 84, 93, 101, 110])->map(fn ($value) => max(3, (int) round($value * $factor)))->values()->all(),
            'percent_data' => collect([2.4, 3.1, 3.8, 4.5, 5.1, 5.7, 6.2])->map(fn ($value) => round($value + (($seed % 5) / 10), 1))->values()->all(),
            'summary_one' => $label . ', bidang usaha, periode',
            'summary_two' => 'Jumlah UMKM dan persentase pertumbuhan',
            'summary_three' => 'Monitoring kinerja wilayah',
        ];
    }
}
