<?php

namespace App\Console\Commands;

use App\Models\PjuData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPjuData extends Command
{
    protected $signature = 'pju:import {file} {--limit=0}';
    protected $description = 'Import PJU data from CSV file';

    public function handle()
    {
        $file = $this->argument('file');
        $limit = (int) $this->option('limit');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Starting import from: {$file}");
        
        // Read file and remove BOM
        $content = file_get_contents($file);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        $lines = explode("\n", $content);
        $header = str_getcsv(array_shift($lines), ';');
        
        // Clean header names
        $header = array_map(function($h) {
            return trim(strtoupper(preg_replace('/[^\w]/', '', $h)));
        }, $header);

        $this->info("Columns found: " . implode(', ', $header));    

        // Map header to table columns
        $columnMap = [
            'IDPEL' => 'idpel',
            'NAMA' => 'nama',
            'NAMAPNJ' => 'namapnj',
            'RT' => 'rt',
            'RW' => 'rw',
            'TARIF' => 'tarif',
            'DAYA' => 'daya',
            'JENISLAYANAN' => 'jenislayanan',
            'NOMORMETERKHW' => 'nomor_meter_kwh',
            'NOMOR_METER_KWH' => 'nomor_meter_kwh',
            'NOMORGARDU' => 'nomor_gardu',
            'NOMOR_GARDU' => 'nomor_gardu',
            'NOMORJURUSANTIANG' => 'nomor_jurusan_tiang',
            'NOMOR_JURUSAN_TIANG' => 'nomor_jurusan_tiang',
            'NAMAGARDU' => 'nama_gardu',
            'NAMA_GARDU' => 'nama_gardu',
            'NOMORMETERPREPAID' => 'nomor_meter_prepaid',
            'NOMOR_METER_PREPAID' => 'nomor_meter_prepaid',
            'KOORDINATX' => 'koordinat_x',
            'KOORDINAT_X' => 'koordinat_x',
            'KOORDINATY' => 'koordinat_y',
            'KOORDINAT_Y' => 'koordinat_y',
            'KDAM' => 'kdam',
            'NAMAKABUPATEN' => 'nama_kabupaten',
            'NAMA_KABUPATEN' => 'nama_kabupaten',
            'NAMAKECAMATAN' => 'nama_kecamatan',
            'NAMA_KECAMATAN' => 'nama_kecamatan',
            'NAMAKELURAHAN' => 'nama_kelurahan',
            'NAMA_KELURAHAN' => 'nama_kelurahan',
        ];

        $headerIndexes = [];
        foreach ($header as $index => $col) {
            if (isset($columnMap[$col])) {
                $headerIndexes[$columnMap[$col]] = $index;
            }
        }

        $this->info("Mapped columns: " . json_encode($headerIndexes));

        $count = 0;
        $batchSize = 500;
        $batch = [];
        $errors = 0;
        $successCount = 0;

        $totalLines = count($lines);
        $maxCount = $limit > 0 ? min($limit, $totalLines) : $totalLines;
        
        $progressBar = $this->output->createProgressBar($maxCount);
        $progressBar->start();

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            if ($limit > 0 && $count >= $limit) break;

            $row = str_getcsv($line, ';');
            $count++;

            $data = ['created_at' => now(), 'updated_at' => now()];
            
            foreach ($headerIndexes as $dbCol => $csvIndex) {
                $value = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : null;
                
                // Handle numeric/decimal fields
                if ($dbCol === 'daya') {
                    $value = is_numeric($value) ? (int)$value : null;
                } elseif (in_array($dbCol, ['koordinat_x', 'koordinat_y'])) {
                    // Fix coordinate format: 108.545.968.411.111 -> 108.545968411111
                    if ($value) {
                        // Remove all dots except the first one
                        $parts = explode('.', $value);
                        if (count($parts) > 2) {
                            $value = $parts[0] . '.' . implode('', array_slice($parts, 1));
                        }
                        $value = is_numeric($value) ? round((float)$value, 10) : null;
                    } else {
                        $value = null;
                    }
                }
                
                $data[$dbCol] = ($value === '' || $value === null) ? null : $value;
            }

            $batch[] = $data;

            if (count($batch) >= $batchSize) {
                try {
                    DB::table('pju_data')->insert($batch);
                    $successCount += count($batch);
                } catch (\Exception $e) {
                    $errors += count($batch);
                    $this->error("\nBatch error: " . $e->getMessage());
                }
                $batch = [];
                $progressBar->advance($batchSize);
            }
        }

        // Insert remaining
        if (!empty($batch)) {
            try {
                DB::table('pju_data')->insert($batch);
                $successCount += count($batch);
            } catch (\Exception $e) {
                $errors += count($batch);
            }
            $progressBar->advance(count($batch));
        }

        $progressBar->finish();

        $this->newLine();
        $this->info("Processed {$count} rows. Successfully imported {$successCount} records! (Errors: {$errors})");
        
        return 0;
    }
}
