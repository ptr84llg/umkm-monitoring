<?php

namespace App\Console\Commands;

use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class ImportRegionsFromWilayahSql extends Command
{
    protected $signature = 'regions:import
        {--file= : Path file wilayah.sql}
        {--source=cahyadsn_wilayah_2025 : Nama sumber data wilayah}
        {--fresh : Hapus data regions dari source yang sama sebelum import}
        {--chunk=1000 : Jumlah data per batch upsert}
        {--limit=0 : Batas jumlah data untuk uji coba; 0 berarti semua data}';

    protected $description = 'Import master wilayah dari file SQL cahyadsn/wilayah ke tabel regions internal sistem.';

    public function handle(): int
    {
        $file = $this->resolveSqlFile();
        $source = (string) $this->option('source');
        $chunkSize = max(100, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));

        if (!$file || !File::exists($file)) {
            $this->error('File wilayah.sql tidak ditemukan.');
            $this->line('Gunakan opsi --file="path\to\wilayah.sql" jika lokasi file berbeda.');

            return self::FAILURE;
        }

        $this->info('Import master wilayah');
        $this->line('File   : '.$file);
        $this->line('Source : '.$source);
        $this->line('Chunk  : '.$chunkSize);
        $this->line('Limit  : '.($limit > 0 ? $limit : 'semua data'));
        $this->newLine();

        try {
            $sql = File::get($file);
        } catch (Throwable $exception) {
            $this->error('Gagal membaca file SQL: '.$exception->getMessage());

            return self::FAILURE;
        }

        preg_match_all("/\('([^']+)'\s*,\s*'((?:[^']|'')*)'\)/", $sql, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            $this->error('Tidak ada pasangan kode-nama yang berhasil dibaca dari file SQL.');

            return self::FAILURE;
        }

        if ($limit > 0) {
            $matches = array_slice($matches, 0, $limit);
        }

        $total = count($matches);
        $now = now();

        if ($this->option('fresh')) {
            if (!$this->confirm("Hapus data regions dari source '{$source}' sebelum import?", true)) {
                $this->warn('Import dibatalkan.');

                return self::FAILURE;
            }

            Region::where('source', $source)->delete();
            $this->info("Data lama dari source '{$source}' sudah dihapus.");
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $batch = [];
        $imported = 0;
        $skipped = 0;
        $levelCounter = [
            Region::LEVEL_PROVINCE => 0,
            Region::LEVEL_CITY => 0,
            Region::LEVEL_DISTRICT => 0,
            Region::LEVEL_VILLAGE => 0,
            'unknown' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($matches as $match) {
                $code = trim($match[1]);
                $name = trim(str_replace("''", "'", $match[2]));

                $level = Region::detectLevel($code);

                if ($level === 'unknown' || $code === '' || $name === '') {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $codeMap = Region::buildCodeMap($code);

                $batch[] = array_merge([
                    'code' => $code,
                    'name' => $name,
                    'level' => $level,
                    'parent_code' => Region::detectParentCode($code),
                    'source' => $source,
                    'is_active' => true,
                    'imported_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $codeMap);

                $levelCounter[$level]++;
                $imported++;

                if (count($batch) >= $chunkSize) {
                    $this->upsertBatch($batch);
                    $batch = [];
                }

                $bar->advance();
            }

            if (!empty($batch)) {
                $this->upsertBatch($batch);
            }

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            $bar->finish();
            $this->newLine(2);
            $this->error('Import gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Import selesai.');
        $this->line('Imported : '.$imported);
        $this->line('Skipped  : '.$skipped);
        $this->newLine();

        $this->table(
            ['Level', 'Jumlah'],
            collect($levelCounter)
                ->map(fn ($count, $level) => [$level, $count])
                ->values()
                ->all()
        );

        return self::SUCCESS;
    }

    protected function resolveSqlFile(): ?string
    {
        $optionFile = $this->option('file');

        if ($optionFile && File::exists($optionFile)) {
            return $optionFile;
        }

        $defaultFile = storage_path('app/private/references/wilayah/cahyadsn-wilayah-master/wilayah-master/db/wilayah.sql');

        if (File::exists($defaultFile)) {
            return $defaultFile;
        }

        $candidates = glob(storage_path('app/private/references/wilayah/cahyadsn-wilayah-master/*/db/wilayah.sql'));

        if (!empty($candidates)) {
            return $candidates[0];
        }

        return null;
    }

    protected function upsertBatch(array $batch): void
    {
        Region::upsert(
            $batch,
            ['code'],
            [
                'name',
                'level',
                'parent_code',
                'province_code',
                'city_code',
                'district_code',
                'village_code',
                'source',
                'is_active',
                'imported_at',
                'updated_at',
            ]
        );
    }
}
