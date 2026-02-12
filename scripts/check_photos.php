<?php
// Quick diagnostic: check photo column values in database
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PjuData;

echo "=== PHOTO COLUMN DIAGNOSTICS ===" . PHP_EOL;

// Count by type
$driveUrl = PjuData::where('photo', 'LIKE', '%drive.google%')->count();
$sampleImg = PjuData::where('photo', 'LIKE', '%dishub_sample%')->count();
$nullPhoto = PjuData::whereNull('photo')->count();
$emptyPhoto = PjuData::where('photo', '')->count();
$hasPhoto = PjuData::whereNotNull('photo')->where('photo', '!=', '')->count();
$total = PjuData::count();

echo "Total records:      $total" . PHP_EOL;
echo "Has photo:          $hasPhoto" . PHP_EOL;
echo "  Drive URL:        $driveUrl" . PHP_EOL;
echo "  Sample img:       $sampleImg" . PHP_EOL;
echo "  Other:            " . ($hasPhoto - $driveUrl - $sampleImg) . PHP_EOL;
echo "NULL photo:         $nullPhoto" . PHP_EOL;
echo "Empty string:       $emptyPhoto" . PHP_EOL;

// Show samples of each type
echo PHP_EOL . "=== SAMPLE PHOTOS ===" . PHP_EOL;

echo PHP_EOL . "--- Unclear records with photos (first 5) ---" . PHP_EOL;
$unclear = PjuData::where(function ($q) {
    $q->whereNull('kdam')->orWhere('kdam', '')->orWhereNotIn('kdam', ['M', 'A']);
})->whereNotNull('photo')->where('photo', '!=', '')->limit(5)->get(['idpel', 'photo', 'koordinat_x', 'koordinat_y']);
foreach ($unclear as $r) {
    echo "  IDPEL: {$r->idpel}" . PHP_EOL;
    echo "  Photo: {$r->photo}" . PHP_EOL;
    echo "  Coords: {$r->koordinat_x}, {$r->koordinat_y}" . PHP_EOL;
    echo PHP_EOL;
}

echo "--- PLN records with photos (first 3) ---" . PHP_EOL;
$pln = PjuData::whereIn('kdam', ['M', 'A'])->whereNotNull('photo')->where('photo', '!=', '')->limit(3)->get(['idpel', 'photo']);
foreach ($pln as $r) {
    echo "  IDPEL: {$r->idpel} => {$r->photo}" . PHP_EOL;
}

echo PHP_EOL . "--- Unclear records WITHOUT photos (first 5) ---" . PHP_EOL;
$noPhoto = PjuData::where(function ($q) {
    $q->whereNull('kdam')->orWhere('kdam', '')->orWhereNotIn('kdam', ['M', 'A']);
})->where(function ($q) {
    $q->whereNull('photo')->orWhere('photo', '');
})->limit(5)->get(['idpel', 'koordinat_x', 'koordinat_y']);
foreach ($noPhoto as $r) {
    echo "  IDPEL: {$r->idpel} | Coords: {$r->koordinat_x}, {$r->koordinat_y}" . PHP_EOL;
}

// Count unclear with vs without photos
$unclearWithPhoto = PjuData::where(function ($q) {
    $q->whereNull('kdam')->orWhere('kdam', '')->orWhereNotIn('kdam', ['M', 'A']);
})->whereNotNull('photo')->where('photo', '!=', '')->count();
$unclearWithoutPhoto = PjuData::where(function ($q) {
    $q->whereNull('kdam')->orWhere('kdam', '')->orWhereNotIn('kdam', ['M', 'A']);
})->where(function ($q) {
    $q->whereNull('photo')->orWhere('photo', '');
})->count();
echo PHP_EOL . "Unclear WITH photo: $unclearWithPhoto" . PHP_EOL;
echo "Unclear WITHOUT photo: $unclearWithoutPhoto" . PHP_EOL;
