<?php
/**
 * Analyze IDPEL codes to determine area code patterns
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PjuData;
use Illuminate\Support\Facades\DB;

echo "=== IDPEL Area Code Analysis ===\n\n";

// Get IDPEL patterns by kecamatan
$codes = DB::table('pju_data')
    ->selectRaw('LEFT(idpel, 6) as code, nama_kecamatan, nama_kabupaten, COUNT(*) as cnt')
    ->whereNotNull('idpel')
    ->whereNotNull('nama_kecamatan')
    ->where('idpel', '!=', '')
    ->groupBy(DB::raw('LEFT(idpel, 6)'), 'nama_kecamatan', 'nama_kabupaten')
    ->orderByDesc('cnt')
    ->limit(50)
    ->get();

echo "Top IDPEL codes by Kecamatan:\n";
echo str_repeat('-', 70) . "\n";
echo sprintf("%-10s %-25s %-20s %s\n", "CODE", "KECAMATAN", "KABUPATEN", "COUNT");
echo str_repeat('-', 70) . "\n";

$areaCodeMap = [];
foreach ($codes as $c) {
    echo sprintf(
        "%-10s %-25s %-20s %d\n",
        $c->code,
        substr($c->nama_kecamatan ?? '-', 0, 25),
        substr($c->nama_kabupaten ?? '-', 0, 20),
        $c->cnt
    );

    // Store mapping
    $key = strtoupper(trim($c->nama_kecamatan ?? ''));
    if (!isset($areaCodeMap[$key]) || $c->cnt > $areaCodeMap[$key]['count']) {
        $areaCodeMap[$key] = [
            'code' => $c->code,
            'kabupaten' => $c->nama_kabupaten,
            'count' => $c->cnt
        ];
    }
}

echo "\n\n=== Area Code Map (for generating missing IDPEL) ===\n";
echo json_encode($areaCodeMap, JSON_PRETTY_PRINT);
