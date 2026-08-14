<?php

namespace App\Support\PublicLanding;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PublicLandingDataFreshness
{
    public static function latest(): array
    {
        $snapshotId = null;
        $timestamp = null;

        if (Schema::hasTable('lss_sync_runs') && Schema::hasColumn('lss_sync_runs', 'completed_at')) {
            $query = DB::table('lss_sync_runs')
                ->whereNotNull('completed_at');

            if (Schema::hasColumn('lss_sync_runs', 'source_system')) {
                $query->where('source_system', 'LSS');
            }

            if (Schema::hasColumn('lss_sync_runs', 'status')) {
                $query->where('status', 'completed');
            }

            $columns = ['completed_at'];
            if (Schema::hasColumn('lss_sync_runs', 'snapshot_id')) {
                $columns[] = 'snapshot_id';
            }

            $row = $query->orderByDesc('completed_at')->first($columns);

            if ($row !== null) {
                $timestamp = $row->completed_at ?? null;
                $snapshotId = isset($row->snapshot_id) ? trim((string) $row->snapshot_id) : null;
            }
        }

        if ($timestamp === null && Schema::hasTable('umkms')) {
            foreach (['source_last_seen_at', 'lss_detail_synced_at'] as $column) {
                if (! Schema::hasColumn('umkms', $column)) {
                    continue;
                }

                $query = DB::table('umkms')->whereNotNull($column);

                if (Schema::hasColumn('umkms', 'source_system')) {
                    $query->where('source_system', 'LSS');
                }

                if (Schema::hasColumn('umkms', 'source_active')) {
                    $query->where('source_active', 1);
                }

                $value = $query->max($column);
                if ($value !== null) {
                    $timestamp = $value;
                    break;
                }
            }
        }

        if ($timestamp === null) {
            return [
                'iso' => null,
                'label' => 'Belum tersedia',
                'snapshot_id' => $snapshotId,
            ];
        }

        try {
            $date = Carbon::parse((string) $timestamp)->timezone((string) config('app.timezone', 'Asia/Jakarta'));
        } catch (\Throwable) {
            return [
                'iso' => null,
                'label' => 'Belum tersedia',
                'snapshot_id' => $snapshotId,
            ];
        }

        return [
            'iso' => $date->toIso8601String(),
            'label' => $date->format('d/m/Y H:i T'),
            'snapshot_id' => $snapshotId,
        ];
    }
}
