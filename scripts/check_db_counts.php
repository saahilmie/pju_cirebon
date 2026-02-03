<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PjuData;

echo "=== Database Record Counts ===\n\n";

$total = PjuData::count();
$withRealIdpel = PjuData::whereRaw("idpel NOT LIKE '% - %'")->whereNotNull('idpel')->count();
$withGeneratedIdpel = PjuData::whereRaw("idpel LIKE '% - %'")->count();
$unclear = PjuData::where(function ($q) {
    $q->whereNull('kdam')
        ->orWhereNotIn('kdam', ['M', 'A'])
        ->orWhere('idpel', 'LIKE', '% - %');
})->count();

echo "Total records: {$total}\n";
echo "With real IDPEL: {$withRealIdpel}\n";
echo "With generated IDPEL (from Dishub): {$withGeneratedIdpel}\n";
echo "Unclear (no KDAM or generated IDPEL): {$unclear}\n";

// Sample of generated IDPELs
echo "\n=== Sample Generated IDPELs ===\n";
$samples = PjuData::whereRaw("idpel LIKE '% - %'")->limit(5)->get(['idpel', 'nama_kecamatan', 'kdam']);
foreach ($samples as $s) {
    echo "- {$s->idpel} | KDAM: " . ($s->kdam ?? 'null') . "\n";
}
