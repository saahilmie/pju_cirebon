<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessUpdateMeterisasi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pju:update-meterisasi {file?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process UPDATE METERISASI.xlsx to update KDAM to M and mark with purple color';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file') ?: 'D:\KP\Data PLN\UPDATE METERISASI.xlsx';
        
        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $this->info("Loading Excel file...");
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows)) {
            $this->error("Excel file is empty.");
            return;
        }

        $headers = array_map('trim', array_map('strtolower', $rows[0]));
        
        // Find IDPEL column index
        $idpelIndex = false;
        foreach ($headers as $index => $header) {
            if (str_contains($header, 'idpel') || str_contains($header, 'id_pel') || str_contains($header, 'id pel')) {
                $idpelIndex = $index;
                break;
            }
        }

        if ($idpelIndex === false) {
            // fallback: let's just look at the first data row and find a 11-12 digit number
            $this->warn("Could not find IDPEL header. Will try to auto-detect from data.");
        }

        $updatedCount = 0;
        $notFoundCount = 0;

        $this->info("Processing rows...");
        
        $bar = $this->output->createProgressBar(count($rows) - 1);

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $idpel = null;

            if ($idpelIndex !== false) {
                $idpel = preg_replace('/[^0-9]/', '', $row[$idpelIndex] ?? '');
            } else {
                // Auto detect
                foreach ($row as $cell) {
                    $cleaned = preg_replace('/[^0-9]/', '', $cell ?? '');
                    if (strlen($cleaned) >= 11 && strlen($cleaned) <= 12) {
                        $idpel = $cleaned;
                        break;
                    }
                }
            }

            if (!$idpel) {
                $bar->advance();
                continue;
            }

            // Find in database
            $record = \App\Models\PjuData::where('idpel', $idpel)->first();
            
            if ($record) {
                $record->kdam = 'M';
                $record->update_color_marker = 'purple'; // Originates from Abodemen
                $record->save();
                $updatedCount++;
            } else {
                $notFoundCount++;
                $this->line("\nIDPEL not found in DB: {$idpel}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Finished! Updated: {$updatedCount}, Not Found: {$notFoundCount}");
    }
}
