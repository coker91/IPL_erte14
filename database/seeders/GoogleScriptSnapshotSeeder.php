<?php

namespace Database\Seeders;

use App\Services\SourceSnapshotImporter;
use Illuminate\Database\Seeder;

class GoogleScriptSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $file = config('ipl.source_file');
        if (! is_file($file)) {
            throw new \RuntimeException('Letakkan snapshot privat pada IPL_SOURCE_FILE terlebih dahulu.');
        }
        $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $count = app(SourceSnapshotImporter::class)->import($data);
        $this->command?->info($count ? "$count unit dari snapshot diimpor." : 'Snapshot sama sudah ada.');
    }
}
