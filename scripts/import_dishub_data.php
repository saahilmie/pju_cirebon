<?php
/**
 * Import Dishub CSV Data to PJU Database
 * 
 * Dishub CSV Column indices (semicolon-delimited):
 *  4: X (coordinate)
 *  5: Y (coordinate)
 *  7: NAMA JALAN → alamat
 * 11: DESA/KEL. → nama_kelurahan
 * 12: KECAMATAN → nama_kecamatan
 * 20: JML LAMPU → jumlah_lampu
 * 21: DAYA LAMPU → daya
 * 22: STATUS LAMPU → kdam (METERISASI/ABONEMEN)
 * 32: IDPEL APP → idpel
 * 34: LINK GAMBAR → for photo URL
 * 
 * For missing IDPEL, generate: {area_code} - {KECAMATAN} / {KABUPATEN}
 * Records without proper IDPEL are marked as "unclear" for RED marker on map
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PjuData;
use Illuminate\Support\Facades\DB;

// Configuration
$csvFiles = [
    'D:\\KP\\Data PLN\\Dishub Kab.CRB\\TOTAL DATABASE CIREBON (1).csv',
    'D:\\KP\\Data PLN\\Dishub Kab.CRB\\TOTAL DATABASE CIREBON (2).csv',
    'D:\\KP\\Data PLN\\Dishub Kab.CRB\\TOTAL DATABASE CIREBON (3).csv',
    'D:\\KP\\Data PLN\\Dishub Kab.CRB\\TOTAL DATABASE CIREBON (4).csv',
];

// Column indices (0-based) for semicolon-delimited CSV
$COL = [
    'kelurahan' => 11,    // DESA/KEL.
    'kecamatan' => 12,    // KECAMATAN
    'alamat' => 7,        // NAMA JALAN
    'jumlah_lampu' => 20, // JML LAMPU
    'daya' => 21,         // DAYA LAMPU
    'kdam' => 22,         // STATUS LAMPU
    'idpel' => 32,        // IDPEL APP
    'link_gambar' => 34,  // LINK GAMBAR
    'x' => 4,             // X coordinate
    'y' => 5,             // Y coordinate
];

// Area code mapping from analysis
$areaCodeMap = [
    'SUMBER' => ['code' => '533313', 'kabupaten' => 'KAB. CIREBON'],
    'ARJAWINANGUN' => ['code' => '533313', 'kabupaten' => 'KAB. CIREBON'],
    'PALIMANAN' => ['code' => '533313', 'kabupaten' => 'KAB. CIREBON'],
    'SUSUKAN' => ['code' => '533313', 'kabupaten' => 'KAB. CIREBON'],
    'DEPOK' => ['code' => '533313', 'kabupaten' => 'KAB. CIREBON'],
    'CILEDUG' => ['code' => '533112', 'kabupaten' => 'KAB. CIREBON'],
    'KEDAWUNG' => ['code' => '533113', 'kabupaten' => 'KAB. CIREBON'],
    'MUNDU' => ['code' => '533113', 'kabupaten' => 'KAB. CIREBON'],
    'PANGENAN' => ['code' => '533512', 'kabupaten' => 'KAB. CIREBON'],
    'BEBER' => ['code' => '533711', 'kabupaten' => 'KAB. CIREBON'],
    'HARJAMUKTI' => ['code' => '533112', 'kabupaten' => 'KOTA CIREBON'],
    'KESAMBI' => ['code' => '533112', 'kabupaten' => 'KOTA CIREBON'],
];

$defaultCode = '533313'; // Default for KAB. CIREBON

function generateIdpel($kecamatan, $areaCodeMap, $defaultCode)
{
    $kec = strtoupper(trim($kecamatan ?? ''));

    if (isset($areaCodeMap[$kec])) {
        $code = $areaCodeMap[$kec]['code'];
        $kab = $areaCodeMap[$kec]['kabupaten'];
    } else {
        $code = $defaultCode;
        $kab = 'KAB. CIREBON';
    }

    return "{$code} - {$kec} / {$kab}";
}

function mapKdam($statusLampu)
{
    $status = strtoupper(trim($statusLampu ?? ''));
    if (strpos($status, 'METER') !== false || strpos($status, 'MTRI') !== false) {
        return 'M';
    } elseif (strpos($status, 'ABON') !== false) {
        return 'A';
    }
    return null; // Unclear
}

function getVal($row, $idx)
{
    return isset($row[$idx]) ? trim($row[$idx]) : null;
}

// Stats
$totalRows = 0;
$imported = 0;
$skipped = 0;
$noIdpelCount = 0;

echo "=== Dishub Data Import Script ===\n\n";

// Check for --dry-run flag
$dryRun = in_array('--dry-run', $argv ?? []);
if ($dryRun) {
    echo "[DRY RUN MODE - No changes will be made]\n\n";
}

// Check for --limit flag
$limit = null;
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int) str_replace('--limit=', '', $arg);
        echo "[LIMITED TO {$limit} ROWS]\n\n";
    }
}

foreach ($csvFiles as $csvFile) {
    if (!file_exists($csvFile)) {
        echo "WARNING: File not found: {$csvFile}\n";
        continue;
    }

    echo "Processing: " . basename($csvFile) . "\n";

    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        echo "ERROR: Cannot open file\n";
        continue;
    }

    // Read header (skip it)
    $header = fgetcsv($handle, 0, ';');
    if (!$header) {
        echo "ERROR: Cannot read header\n";
        fclose($handle);
        continue;
    }

    echo "  Header columns: " . count($header) . "\n";

    $fileImported = 0;
    $fileSkipped = 0;

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $totalRows++;

        if ($limit && $totalRows > $limit) {
            break;
        }

        // Extract data using fixed column indices
        $kelurahan = getVal($row, $COL['kelurahan']);
        $kecamatan = getVal($row, $COL['kecamatan']);
        $alamat = getVal($row, $COL['alamat']);
        $jumlahLampu = (int) getVal($row, $COL['jumlah_lampu']) ?: 1;
        $daya = (int) preg_replace('/[^0-9]/', '', getVal($row, $COL['daya']) ?? '');
        $statusLampu = getVal($row, $COL['kdam']);
        $idpelRaw = getVal($row, $COL['idpel']);
        $linkGambar = getVal($row, $COL['link_gambar']);
        $xCoord = getVal($row, $COL['x']);
        $yCoord = getVal($row, $COL['y']);

        // Skip empty rows
        if (!$kecamatan && !$idpelRaw && !$alamat) {
            $fileSkipped++;
            continue;
        }

        // Determine IDPEL and unclear status
        $hasRealIdpel = !empty($idpelRaw) && preg_match('/^\d{9,15}$/', $idpelRaw);
        $idpel = $hasRealIdpel ? $idpelRaw : generateIdpel($kecamatan, $areaCodeMap, $defaultCode);

        // Map KDAM
        $kdam = mapKdam($statusLampu);

        // Determine if this is "unclear" (no proper IDPEL or no KDAM)
        $isUnclear = !$hasRealIdpel || $kdam === null;

        if (!$hasRealIdpel) {
            $noIdpelCount++;
        }

        // Prepare data for insert
        $data = [
            'idpel' => $idpel,
            'nama_kelurahan' => ucwords(strtolower($kelurahan ?? '')),
            'nama_kecamatan' => strtoupper($kecamatan ?? ''),
            'nama_kabupaten' => 'KAB. CIREBON',
            'alamat' => $alamat,
            'kdam' => $isUnclear ? null : $kdam, // null KDAM = unclear = RED marker
            'jumlah_lampu' => $jumlahLampu,
            'jumlah_lampu_source' => 'manual',
            'daya' => $daya ?: null,
            'is_idpel_main' => $hasRealIdpel, // Only real IDPELs can be main
            // Note: Coordinates in CSV are in UTM format (X/Y), not lat/lng - skip them
            'koordinat_x' => null,
            'koordinat_y' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (!$dryRun) {
            try {
                PjuData::create($data);
                $fileImported++;
            } catch (\Exception $e) {
                echo "  ERROR row {$totalRows}: " . $e->getMessage() . "\n";
                $fileSkipped++;
            }
        } else {
            $fileImported++;

            // Show sample data in dry run
            if ($totalRows <= 5) {
                echo "  Sample row {$totalRows}:\n";
                echo "    IDPEL: {$idpel}" . ($hasRealIdpel ? "" : " (generated)") . "\n";
                echo "    Kecamatan: {$kecamatan}, Kelurahan: {$kelurahan}\n";
                echo "    KDAM: " . ($kdam ?? 'unclear') . ", Status: {$statusLampu}\n";
                echo "    Lampu: {$jumlahLampu}, Daya: {$daya}\n";
                echo "\n";
            }
        }

        // Progress indicator
        if ($totalRows % 1000 === 0) {
            echo "  Processed {$totalRows} rows...\n";
        }
    }

    fclose($handle);

    echo "  Imported: {$fileImported}, Skipped: {$fileSkipped}\n\n";

    $imported += $fileImported;
    $skipped += $fileSkipped;

    if ($limit && $totalRows >= $limit) {
        echo "Limit reached, stopping.\n\n";
        break;
    }
}

echo "=== Summary ===\n";
echo "Total rows processed: {$totalRows}\n";
echo "Imported: {$imported}\n";
echo "Skipped: {$skipped}\n";
echo "Records without real IDPEL (generated): {$noIdpelCount}\n";

if ($dryRun) {
    echo "\n[DRY RUN - No actual changes were made]\n";
    echo "Run without --dry-run flag to actually import data.\n";
}
