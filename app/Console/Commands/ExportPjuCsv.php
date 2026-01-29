<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PjuData;

class ExportPjuCsv extends Command
{
    protected $signature = 'pju:export-csv {--output=storage/pju_export.csv}';
    protected $description = 'Export PJU data with coordinates to CSV for image matching';

    public function handle()
    {
        $output = $this->option('output');

        $this->info('Exporting PJU data with coordinates...');

        $data = PjuData::whereNotNull('koordinat_x')
            ->whereNotNull('koordinat_y')
            ->get(['id', 'idpel', 'nama', 'koordinat_x', 'koordinat_y', 'nama_kabupaten', 'nama_kecamatan', 'nama_kelurahan']);

        $filepath = base_path($output);
        $file = fopen($filepath, 'w');

        fputcsv($file, ['id', 'idpel', 'nama', 'koordinat_x', 'koordinat_y', 'nama_kabupaten', 'nama_kecamatan', 'nama_kelurahan']);

        foreach ($data as $row) {
            fputcsv($file, [
                $row->id,
                $row->idpel,
                $row->nama,
                $row->koordinat_x,
                $row->koordinat_y,
                $row->nama_kabupaten,
                $row->nama_kecamatan,
                $row->nama_kelurahan,
            ]);
        }

        fclose($file);

        $this->info("Exported {$data->count()} records to: {$filepath}");

        return 0;
    }
}
