<?php
/**
 * Export PJU data to CSV for image coordinate matching
 * 
 * Usage: php artisan tinker scripts/export_for_matching.php
 * Output: D:\KP\pju-cirebon\storage\pju_export.csv
 */

$data = \App\Models\PjuData::whereNotNull('koordinat_x')
    ->whereNotNull('koordinat_y')
    ->get(['id', 'idpel', 'nama', 'koordinat_x', 'koordinat_y', 'nama_kabupaten', 'nama_kecamatan', 'nama_kelurahan']);

$filepath = storage_path('pju_export.csv');
$file = fopen($filepath, 'w');

// Write headers
fputcsv($file, ['id', 'idpel', 'nama', 'koordinat_x', 'koordinat_y', 'nama_kabupaten', 'nama_kecamatan', 'nama_kelurahan']);

// Write data
foreach ($data as $row) {
    fputcsv($file, [
        $row->id,
        $row->idpel,
        $row->nama,
        $row->koordinat_x,
        $row->koordinat_y,
        $row->nama_kabupaten,
        $row->nama_kecamatan,
        $row->nama_kelurahan,
    ]);
}

fclose($file);

echo "Exported " . count($data) . " records to: $filepath\n";
