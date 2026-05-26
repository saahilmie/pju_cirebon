<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\PjuData;

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
    protected $description = 'Process METERISASI files to update KDAM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->processFile('D:\KP\Data PLN\DAFTAR PJU SUDAH METERISASI.xlsx', 'M', 'orange');
        $this->info('');
        $this->processFile('D:\KP\Data PLN\abodemen sisa.xlsx', 'A', null);
        $this->info('Finished processing all files!');
    }

    private function processFile($filePath, $kdam, $colorMarker)
    {
        if (!file_exists($filePath)) {
            $this->error("File not found: " . $filePath);
            return;
        }

        $this->info("Loading Excel file: " . basename($filePath));
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        array_shift($rows); // Remove header row

        $this->info("Collecting IDPELs...");
        $idpels = [];
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $cleaned = preg_replace('/[^0-9]/', '', (string)$cell);
                if (strlen($cleaned) >= 11 && strlen($cleaned) <= 12) {
                    $idpels[] = $cleaned;
                    break;
                }
            }
        }

        $idpels = array_unique($idpels);
        $this->info("Found " . count($idpels) . " unique IDPELs.");

        if (count($idpels) > 0) {
            $this->info("Updating database...");
            $updatedCount = PjuData::whereIn('idpel', $idpels)->update([
                'kdam' => $kdam,
                'update_color_marker' => $colorMarker
            ]);
            $this->info("Finished " . basename($filePath) . "! Updated: $updatedCount rows.");
        }
    }
}
