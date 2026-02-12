<?php
/**
 * Match Dishub Photos to Database Records (Optimized v2)
 * 
 * Uses indexed hashmaps for O(1) lookups instead of scanning 110k records.
 * 
 * Usage:
 *   php scripts/match_photos_to_db.php                  # Full run
 *   php scripts/match_photos_to_db.php --dry-run        # Preview only
 *   php scripts/match_photos_to_db.php --limit=100      # Test with 100
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

$photosDir = __DIR__ . '/../public/dishub_photos';
$COL_LINK = 34;
$COL_IDPEL = 32;
$COL_KEC = 12;

// Parse CLI flags
$dryRun = in_array('--dry-run', $argv ?? []);
$limit = 0;
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int) str_replace('--limit=', '', $arg);
    }
}

echo "=== DISHUB PHOTO MATCHER v2 ===" . PHP_EOL;
if ($dryRun)
    echo "[DRY RUN]" . PHP_EOL;
if ($limit)
    echo "[LIMIT: {$limit}]" . PHP_EOL;

// Step 1: Index local photos
echo PHP_EOL . "Step 1: Indexing local photos..." . PHP_EOL;
if (!is_dir($photosDir)) {
    echo "ERROR: Photos dir not found: {$photosDir}" . PHP_EOL;
    exit(1);
}
$localPhotos = [];
foreach (new DirectoryIterator($photosDir) as $file) {
    if ($file->isDot() || $file->isDir())
        continue;
    $ext = strtolower($file->getExtension());
    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $localPhotos[pathinfo($file->getFilename(), PATHINFO_FILENAME)] = $file->getFilename();
    }
}
echo "  Indexed: " . count($localPhotos) . " photos" . PHP_EOL;

// Step 2: Build DB indexes (OPTIMIZED - by kecamatan)
echo PHP_EOL . "Step 2: Building DB index by kecamatan..." . PHP_EOL;

// Build kecamatan -> [record_ids] index
$dbByKecamatan = []; // kecamatan => queue of IDs
$records = DB::table('pju_data')
    ->select('id', 'idpel', 'nama_kecamatan')
    ->where('photo', 'LIKE', '%dishub_sample%')
    ->orderBy('id')
    ->get();

foreach ($records as $r) {
    $kec = strtoupper(trim($r->nama_kecamatan ?? ''));
    if ($kec) {
        if (!isset($dbByKecamatan[$kec])) {
            $dbByKecamatan[$kec] = [];
        }
        $dbByKecamatan[$kec][] = $r->id;
    }
}

$totalRecords = count($records);
$kecCount = count($dbByKecamatan);
echo "  Records: {$totalRecords}" . PHP_EOL;
echo "  Kecamatan groups: {$kecCount}" . PHP_EOL;
foreach (array_slice($dbByKecamatan, 0, 5, true) as $k => $ids) {
    echo "    {$k}: " . count($ids) . " records" . PHP_EOL;
}

// Track position in each kecamatan queue (round-robin assignment)
$kecPosition = [];
foreach ($dbByKecamatan as $k => $ids) {
    $kecPosition[$k] = 0;
}

// Step 3: Process CSVs
echo PHP_EOL . "Step 3: Processing CSVs..." . PHP_EOL;

$stats = [
    'total' => 0,
    'has_link' => 0,
    'photo_found' => 0,
    'photo_missing' => 0,
    'matched' => 0,
    'no_kec' => 0,
    'kec_exhausted' => 0
];
$updates = [];

foreach ($csvFiles as $csvFile) {
    if (!file_exists($csvFile)) {
        echo "  SKIP: " . basename($csvFile) . " (not found)" . PHP_EOL;
        continue;
    }
    echo "  Processing: " . basename($csvFile) . PHP_EOL;

    $handle = fopen($csvFile, 'r');
    if (!$handle)
        continue;
    fgetcsv($handle, 0, ';'); // skip header

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $stats['total']++;
        if ($limit > 0 && $stats['total'] > $limit)
            break;

        $link = isset($row[$COL_LINK]) ? trim($row[$COL_LINK]) : '';
        if (empty($link) || strpos($link, 'drive.google.com') === false)
            continue;
        $stats['has_link']++;

        // Extract Drive file ID
        if (!preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $link, $m))
            continue;
        $fileId = $m[1];

        // Check local photo
        if (!isset($localPhotos[$fileId])) {
            $stats['photo_missing']++;
            continue;
        }
        $stats['photo_found']++;

        // Match by kecamatan
        $kec = isset($row[$COL_KEC]) ? strtoupper(trim($row[$COL_KEC])) : '';
        if (!$kec || !isset($dbByKecamatan[$kec])) {
            $stats['no_kec']++;
            continue;
        }

        // Get next available record for this kecamatan (O(1) lookup)
        $pos = $kecPosition[$kec];
        if ($pos >= count($dbByKecamatan[$kec])) {
            $stats['kec_exhausted']++;
            continue;
        }

        $dbId = $dbByKecamatan[$kec][$pos];
        $kecPosition[$kec]++;

        $updates[$dbId] = '/dishub_photos/' . $localPhotos[$fileId];
        $stats['matched']++;

        if ($stats['total'] % 10000 === 0) {
            echo "    Progress: {$stats['total']} rows, {$stats['matched']} matched" . PHP_EOL;
        }
    }
    fclose($handle);
    if ($limit > 0 && $stats['total'] > $limit)
        break;
}

// Step 4: Apply
echo PHP_EOL . "Step 4: Applying updates..." . PHP_EOL;
if (!$dryRun && count($updates) > 0) {
    $applied = 0;
    $chunks = array_chunk($updates, 1000, true);
    foreach ($chunks as $chunk) {
        DB::beginTransaction();
        foreach ($chunk as $id => $path) {
            DB::table('pju_data')->where('id', $id)->update(['photo' => $path]);
            $applied++;
        }
        DB::commit();
        echo "  Applied: {$applied}/" . count($updates) . PHP_EOL;
    }
    echo "  DONE!" . PHP_EOL;
} else {
    echo "  " . ($dryRun ? "DRY RUN - " : "") . count($updates) . " would be updated" . PHP_EOL;
}

// Summary
echo PHP_EOL . "=== RESULTS ===" . PHP_EOL;
echo "CSV rows:          {$stats['total']}" . PHP_EOL;
echo "Has Drive link:    {$stats['has_link']}" . PHP_EOL;
echo "Photo found:       {$stats['photo_found']}" . PHP_EOL;
echo "Photo missing:     {$stats['photo_missing']}" . PHP_EOL;
echo "Matched to DB:     {$stats['matched']}" . PHP_EOL;
echo "No kecamatan:      {$stats['no_kec']}" . PHP_EOL;
echo "Kec exhausted:     {$stats['kec_exhausted']}" . PHP_EOL;
echo "TOTAL UPDATED:     " . count($updates) . PHP_EOL;

if ($dryRun && count($updates) > 0) {
    echo PHP_EOL . "Sample:" . PHP_EOL;
    $i = 0;
    foreach ($updates as $id => $path) {
        echo "  ID {$id} => {$path}" . PHP_EOL;
        if (++$i >= 3)
            break;
    }
}
