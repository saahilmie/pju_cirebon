<?php
$pju = App\Models\PjuData::where('nama_kecamatan', 'KEDAWUNG')->first();
try {
    $pju->update(['nama_kecamatan' => 'KEDAWUNGG']);
    echo 'Success';
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
