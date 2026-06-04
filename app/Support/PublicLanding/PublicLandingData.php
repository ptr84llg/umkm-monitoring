<?php

namespace App\Support\PublicLanding;

final class PublicLandingData
{
    public static function summary(): array
    {
        return [
            'coverage_label' => 'Kota Lubuklinggau',
            'updated_at_label' => '04 Juni 2026',
            'public_safe_label' => 'Public-safe',
            'total_umkm' => '6.879',
            'mapped_umkm' => '6.412',
            'mapped_percent' => '93,2%',
            'dominant_category' => 'Kuliner',
            'dominant_category_percent' => '38,6%',
            'active_regions' => '72',
            'coverage_percent' => '100%',
            'source_label' => 'Data agregat publik',
        ];
    }

    public static function heroCards(): array
    {
        $summary = self::summary();

        return [
            [
                'icon_class' => 'is-green',
                'icon_path' => 'M3 21V7l7-4 7 4v14h-5v-6H8v6H3Zm16 0V9h2v12h-2Z',
                'chip' => 'Data agregat',
                'label' => 'Total UMKM',
                'value' => $summary['total_umkm'],
                'context' => 'Unit usaha tercatat',
                'progress_class' => 'w-84',
                'foot_label' => $summary['source_label'],
                'foot_value' => '+8,4%',
            ],
            [
                'icon_class' => 'is-blue',
                'icon_path' => 'M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z',
                'chip' => $summary['public_safe_label'],
                'label' => 'Terpetakan',
                'value' => $summary['mapped_umkm'],
                'context' => $summary['mapped_percent'].' dari total',
                'progress_class' => 'w-93',
                'foot_label' => 'Unit terpetakan',
                'foot_value' => '+7,2%',
            ],
            [
                'icon_class' => 'is-gold',
                'icon_path' => 'M4 10.5 5.4 5h13.2L20 10.5V12a3 3 0 0 1-5 2.24A3 3 0 0 1 12 15a3 3 0 0 1-3-0.76A3 3 0 0 1 4 12v-1.5ZM6 16h12v5H6v-5Zm2 2v1h8v-1H8Z',
                'chip' => 'Kategori',
                'label' => 'Kategori dominan',
                'value' => $summary['dominant_category'],
                'context' => $summary['dominant_category_percent'].' dari total',
                'progress_class' => 'w-39',
                'foot_label' => 'Kategori terbanyak',
                'foot_value' => 'Insight',
            ],
            [
                'icon_class' => 'is-purple',
                'icon_path' => 'm12 3 8 4-8 4-8-4 8-4Zm-6.5 7.2L12 13.5l6.5-3.3L20 11l-8 4-8-4 1.5-.8Zm0 4L12 17.5l6.5-3.3L20 15l-8 4-8-4 1.5-.8Z',
                'chip' => 'Wilayah',
                'label' => 'Wilayah aktif',
                'value' => $summary['active_regions'],
                'context' => 'Kelurahan memiliki data',
                'progress_class' => 'w-100',
                'foot_label' => 'Cakupan wilayah',
                'foot_value' => $summary['coverage_percent'],
            ],
        ];
    }

    public static function footerMetrics(): array
    {
        $summary = self::summary();

        return [
            [
                'value' => $summary['active_regions'],
                'label' => 'Kelurahan aktif',
            ],
            [
                'value' => $summary['mapped_umkm'],
                'label' => 'UMKM terpetakan',
            ],
            [
                'value' => $summary['coverage_percent'],
                'label' => 'Wilayah tercakup',
            ],
        ];
    }

    public static function mapPreview(): array
    {
        return [
            'region_label' => 'Kota Lubuklinggau',
            'note' => 'Data agregat dan peta bersifat public-safe. Detail sensitif hanya tersedia bagi pengguna berizin.',
            'clusters' => [
                [
                    'class' => 'cluster-a',
                    'value' => '128',
                ],
                [
                    'class' => 'cluster-b',
                    'value' => '96',
                ],
                [
                    'class' => 'cluster-c',
                    'value' => '74',
                ],
            ],
        ];
    }

    public static function analytics(): array
    {
        $summary = self::summary();

        return [
            'updated_at_label' => $summary['updated_at_label'],
            'scale' => [
                'total' => $summary['total_umkm'],
                'items' => [
                    [
                        'class' => 'is-mikro',
                        'label' => 'Mikro',
                        'percent' => '56,5%',
                    ],
                    [
                        'class' => 'is-kecil',
                        'label' => 'Kecil',
                        'percent' => '29,5%',
                    ],
                    [
                        'class' => 'is-menengah',
                        'label' => 'Menengah',
                        'percent' => '14,0%',
                    ],
                ],
            ],
            'trend_points' => [
                [
                    'class' => 'point-01',
                    'value' => '4.120',
                ],
                [
                    'class' => 'point-02',
                    'value' => '4.650',
                ],
                [
                    'class' => 'point-03',
                    'value' => '5.210',
                ],
                [
                    'class' => 'point-04',
                    'value' => '5.980',
                ],
                [
                    'class' => 'point-05',
                    'value' => $summary['total_umkm'],
                ],
            ],
            'districts' => [
                [
                    'label' => 'Lubuklinggau Timur I',
                    'value' => '1.254',
                    'bar_class' => 'bar-w-92',
                ],
                [
                    'label' => 'Lubuklinggau Barat I',
                    'value' => '1.102',
                    'bar_class' => 'bar-w-80',
                ],
                [
                    'label' => 'Lubuklinggau Selatan I',
                    'value' => '1.021',
                    'bar_class' => 'bar-w-72',
                ],
                [
                    'label' => 'Lubuklinggau Utara II',
                    'value' => '889',
                    'bar_class' => 'bar-w-64',
                ],
                [
                    'label' => 'Lubuklinggau Timur II',
                    'value' => '792',
                    'bar_class' => 'bar-w-56',
                ],
            ],
        ];
    }
}