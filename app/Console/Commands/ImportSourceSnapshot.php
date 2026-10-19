<?php

namespace App\Console\Commands;

use App\Services\SourceSnapshotImporter;
use Illuminate\Console\Command;

class ImportSourceSnapshot extends Command
{
    protected $signature = 'ipl:import-source {file? : JSON snapshot privat}';

    protected $description = 'Impor rumah, rekap legacy, rekening dan saldo awal tanpa merekayasa transaksi lama';

    public function handle(SourceSnapshotImporter $importer): int
    {
        $file = $this->argument('file') ?: config('ipl.source_file');
        if (! is_file($file)) {
            $this->error('File snapshot tidak ditemukan: '.$file);

            return self::FAILURE;
        }
        try {
            $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $n = $importer->import($data);
            $this->info($n ? "Berhasil mengimpor $n unit. Tahun mengikuti file snapshot; periksa kembali tahun sumber." : 'Snapshot yang sama sudah ada; tidak ada data digandakan.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
