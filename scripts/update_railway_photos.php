<?php
/**
 * Fix Railway Photo URLs — FAST version using batch SQL
 * 
 * Part 1: Single SQL to convert ALL old Drive URLs at once
 * Part 2: Fill missing photos from CSV
 * 
 * Usage:
 *   php scripts/update_railway_photos.php --host=HOST --port=PORT --password=PASS
 *   php scripts/update_railway_photos.php --host=HOST --port=PORT --password=PASS --dry-run
 */

$args = [];
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--') === 0) {
        $parts = explode('=', substr($arg, 2), 2);
        $args[$parts[0]] = $parts[1] ?? true;
    }
}

$host = $args['host'] ?? null;
$port = $args['port'] ?? '5432';
$dbname = $args['db'] ?? 'railway';
$user = $args['user'] ?? 'postgres';
$password = $args['password'] ?? null;
$dryRun = isset($args['dry-run']);

if (!$host || !$password) {
    echo "USAGE: php scripts/update_railway_photos.php --host=HOST --port=PORT --password=PASS [--dry-run]" . PHP_EOL;
    exit(1);
}

echo "============================================" . PHP_EOL;
echo "  Railway Photo URL Fixer (FAST)" . PHP_EOL;
echo "============================================" . PHP_EOL;
if ($dryRun)
    echo "[DRY RUN]" . PHP_EOL;
echo PHP_EOL;

// Connect
echo "Connecting to {$host}:{$port}..." . PHP_EOL;
$dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connected!" . PHP_EOL . PHP_EOL;
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Show current state
echo "=== Current Photo Status ===" . PHP_EOL;
$stmt = $pdo->query("SELECT COUNT(*) FROM pju_data");
echo "Total records: " . $stmt->fetchColumn() . PHP_EOL;
$stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo LIKE '%drive.google.com%'");
$oldDrive = $stmt->fetchColumn();
echo "Old Drive URLs: {$oldDrive}" . PHP_EOL;
$stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo LIKE '%lh3.googleusercontent%'");
$newDrive = $stmt->fetchColumn();
echo "New lh3 URLs (already fixed): {$newDrive}" . PHP_EOL;
$stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo IS NULL OR photo = ''");
$noPhoto = $stmt->fetchColumn();
echo "No photo: {$noPhoto}" . PHP_EOL;
$stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo NOT LIKE '%drive%' AND photo NOT LIKE '%lh3%' AND photo IS NOT NULL AND photo != ''");
$otherPhoto = $stmt->fetchColumn();
echo "Other photos (uploads): {$otherPhoto}" . PHP_EOL;

// Show sample old URL
if ($oldDrive > 0) {
    $stmt = $pdo->query("SELECT photo FROM pju_data WHERE photo LIKE '%drive.google.com%' LIMIT 1");
    $sample = $stmt->fetchColumn();
    echo "Sample old URL: " . substr($sample, 0, 80) . "..." . PHP_EOL;
}

// ==========================================
// PART 1: Batch convert Drive URLs using SQL REPLACE
// ==========================================
echo PHP_EOL . "=== PART 1: Convert Drive URLs (batch SQL) ===" . PHP_EOL;

if ($oldDrive > 0) {
    // Strategy: Extract file ID using SQL regex and rebuild URL
    // Old formats:
    //   https://drive.google.com/thumbnail?id=FILE_ID&sz=w800
    //   https://drive.google.com/file/d/FILE_ID/view
    //   https://drive.google.com/open?id=FILE_ID
    // New format:
    //   https://lh3.googleusercontent.com/d/FILE_ID

    // For thumbnail?id=XXX format (most common from import script)
    $sql1 = "UPDATE pju_data 
             SET photo = 'https://lh3.googleusercontent.com/d/' || 
                         SUBSTRING(photo FROM 'id=([a-zA-Z0-9_-]+)')
             WHERE photo LIKE '%drive.google.com/thumbnail%id=%'
               AND SUBSTRING(photo FROM 'id=([a-zA-Z0-9_-]+)') IS NOT NULL";

    // For /file/d/XXX/ format
    $sql2 = "UPDATE pju_data 
             SET photo = 'https://lh3.googleusercontent.com/d/' || 
                         SUBSTRING(photo FROM '/d/([a-zA-Z0-9_-]+)')
             WHERE photo LIKE '%drive.google.com%/d/%'
               AND photo NOT LIKE '%lh3.googleusercontent%'
               AND SUBSTRING(photo FROM '/d/([a-zA-Z0-9_-]+)') IS NOT NULL";

    // For open?id=XXX format
    $sql3 = "UPDATE pju_data 
             SET photo = 'https://lh3.googleusercontent.com/d/' || 
                         SUBSTRING(photo FROM 'id=([a-zA-Z0-9_-]+)')
             WHERE photo LIKE '%drive.google.com/open%id=%'
               AND photo NOT LIKE '%lh3.googleusercontent%'
               AND SUBSTRING(photo FROM 'id=([a-zA-Z0-9_-]+)') IS NOT NULL";

    if ($dryRun) {
        // Count how many would be affected
        $stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo LIKE '%drive.google.com/thumbnail%id=%'");
        echo "  thumbnail?id= format: " . $stmt->fetchColumn() . " records" . PHP_EOL;
        $stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo LIKE '%drive.google.com%/d/%' AND photo NOT LIKE '%lh3.googleusercontent%'");
        echo "  /file/d/ format: " . $stmt->fetchColumn() . " records" . PHP_EOL;
        $stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo LIKE '%drive.google.com/open%id=%' AND photo NOT LIKE '%lh3.googleusercontent%'");
        echo "  open?id= format: " . $stmt->fetchColumn() . " records" . PHP_EOL;
        echo "  [DRY RUN - no changes made]" . PHP_EOL;
    } else {
        echo "  Converting thumbnail?id= format..." . PHP_EOL;
        $count1 = $pdo->exec($sql1);
        echo "    Updated: {$count1}" . PHP_EOL;

        echo "  Converting /file/d/ format..." . PHP_EOL;
        $count2 = $pdo->exec($sql2);
        echo "    Updated: {$count2}" . PHP_EOL;

        echo "  Converting open?id= format..." . PHP_EOL;
        $count3 = $pdo->exec($sql3);
        echo "    Updated: {$count3}" . PHP_EOL;

        echo "  TOTAL converted: " . ($count1 + $count2 + $count3) . PHP_EOL;
    }
} else {
    echo "  No old Drive URLs to convert." . PHP_EOL;
}

// ==========================================
// PART 2: Fill missing photos from CSV
// ==========================================
echo PHP_EOL . "=== PART 2: Fill missing photos ===" . PHP_EOL;

$csvFiles = [
    'D:\\KP\\Data PLN\\Dishub Kab.CRB\\TOTAL DATABASE CIREBON (1).csv',
    'D:\\KP\\Data PLN\\Dishub Kab.CRB\\TOTAL DATABASE CIREBON (2).csv',
    'D:\\KP\\Data PLN\\Dishub Kab.CRB\\TOTAL DATABASE CIREBON (3).csv',
    'D:\\KP\\Data PLN\\Dishub Kab.CRB\\TOTAL DATABASE CIREBON (4).csv',
];
$COL_LINK = 34;
$COL_KEC = 12;

$stmt = $pdo->query("SELECT id, nama_kecamatan FROM pju_data WHERE photo IS NULL OR photo = '' ORDER BY id");
$dbByKec = [];
while ($row = $stmt->fetch()) {
    $kec = strtoupper(trim($row['nama_kecamatan'] ?? ''));
    if ($kec) {
        if (!isset($dbByKec[$kec]))
            $dbByKec[$kec] = [];
        $dbByKec[$kec][] = $row['id'];
    }
}
$missing = array_sum(array_map('count', $dbByKec));
echo "Records without photos: {$missing}" . PHP_EOL;

$kecPos = [];
foreach ($dbByKec as $k => $ids)
    $kecPos[$k] = 0;

$matched = 0;
$updates = [];

foreach ($csvFiles as $csvFile) {
    if (!file_exists($csvFile))
        continue;
    echo "  Processing: " . basename($csvFile) . PHP_EOL;
    $handle = fopen($csvFile, 'r');
    if (!$handle)
        continue;
    fgetcsv($handle, 0, ';');

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $link = isset($row[$COL_LINK]) ? trim($row[$COL_LINK]) : '';
        if (empty($link) || strpos($link, 'drive.google.com') === false)
            continue;
        if (!preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $link, $m) && !preg_match('/id=([a-zA-Z0-9_-]+)/', $link, $m))
            continue;
        $driveUrl = "https://lh3.googleusercontent.com/d/{$m[1]}";

        $kec = isset($row[$COL_KEC]) ? strtoupper(trim($row[$COL_KEC])) : '';
        if (!$kec || !isset($dbByKec[$kec]))
            continue;
        if ($kecPos[$kec] >= count($dbByKec[$kec]))
            continue;

        $dbId = $dbByKec[$kec][$kecPos[$kec]++];
        $updates[$dbId] = $driveUrl;
        $matched++;
    }
    fclose($handle);
}

echo "  Matched: {$matched}" . PHP_EOL;

if (!$dryRun && count($updates) > 0) {
    $stmt = $pdo->prepare("UPDATE pju_data SET photo = :photo WHERE id = :id");
    $pdo->beginTransaction();
    $i = 0;
    foreach ($updates as $id => $url) {
        $stmt->execute(['photo' => $url, 'id' => $id]);
        $i++;
        if ($i % 500 === 0) {
            $pdo->commit();
            $pdo->beginTransaction();
            echo "  Applied: {$i}/" . count($updates) . PHP_EOL;
        }
    }
    $pdo->commit();
    echo "  Applied: {$i}/" . count($updates) . PHP_EOL;
} else {
    echo "  " . ($dryRun ? "[DRY RUN] " : "") . count($updates) . " would be updated" . PHP_EOL;
}

// Final verification
echo PHP_EOL . "=== FINAL RESULTS ===" . PHP_EOL;
$stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo LIKE '%lh3.googleusercontent%'");
echo "lh3 URLs (new format): " . $stmt->fetchColumn() . PHP_EOL;
$stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo LIKE '%drive.google.com%'");
echo "Old Drive URLs (remaining): " . $stmt->fetchColumn() . PHP_EOL;
$stmt = $pdo->query("SELECT COUNT(*) FROM pju_data WHERE photo IS NULL OR photo = ''");
echo "No photo: " . $stmt->fetchColumn() . PHP_EOL;
$stmt = $pdo->query("SELECT photo FROM pju_data WHERE photo LIKE '%lh3.googleusercontent%' LIMIT 1");
$sample = $stmt->fetchColumn();
if ($sample)
    echo "Sample new URL: {$sample}" . PHP_EOL;
echo PHP_EOL . "DONE!" . PHP_EOL;
