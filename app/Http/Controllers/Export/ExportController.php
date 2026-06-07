<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Audit\ExportLog;
use App\Services\Audit\AuditLogger;
use App\Services\Export\ReportExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function form()
    {
        return view('pages.export.form');
    }

    public function generate(Request $request, ReportExportService $service, AuditLogger $audit)
    {
        abort_unless($request->user()->hasPermission('export.sensitive'), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:250'],
            'format' => ['required', 'in:json,csv'],
            'report_type' => ['required', 'in:umkm_ringkas,legalitas_status,klasifikasi_usaha,wilayah,kinerja_periodik,survei,validasi_ahli,all'],
        ]);

        $logsToday = ExportLog::query()
            ->where('actor_user_id', $request->user()->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        abort_if($logsToday >= (int) config('umkm.export.max_per_day', 20), 429, 'Batas ekspor harian tercapai');

        $dataset = $service->reports($validated);

        if ($validated['report_type'] !== 'all') {
            $dataset = [$validated['report_type'] => $dataset[$validated['report_type']] ?? []];
        }

        $allowSensitive = $request->user()->hasPermission('umkm.sensitive.contact')
            && $request->user()->hasPermission('umkm.sensitive.legality');

        $dataset = $service->applyMasking($dataset, $allowSensitive);
        $watermark = $service->watermark([
            'role' => implode(',', $request->user()->roles->pluck('code')->all()),
            'user_id' => $request->user()->id,
            'reason' => $validated['reason'],
        ]);

        $rowCount = collect($dataset)->sum(fn ($rows) => is_array($rows) ? count($rows) : 0);
        $exportLog = $service->logExport([
            'actor_user_id' => $request->user()->id,
            'export_type' => $validated['report_type'],
            'export_reason' => $validated['reason'],
            'watermark_token' => $watermark,
            'status' => 'generated',
            'exported_at' => now(),
        ]);

        $audit->log('export.generate', $request, 'export_logs', $exportLog->id, [], [
            'filter' => $validated,
            'row_count' => $rowCount,
            'format' => $validated['format'],
            'report_type' => $validated['report_type'],
        ]);

        return response()->json([
            'data' => $dataset,
            'meta' => [
                'watermark' => $watermark,
                'rows' => $rowCount,
                'format' => $validated['format'],
            ],
        ]);
    }
}
