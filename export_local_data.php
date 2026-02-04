<?php

/**
 * Export local database data to SQL INSERT statements
 * Run with: php export_local_data.php > pju_data_export.sql
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PjuData;
use Illuminate\Support\Facades\DB;

// Export users
echo "-- Users Export\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

$users = User::all();
foreach ($users as $user) {
    $name = addslashes($user->name);
    $email = addslashes($user->email);
    $password = addslashes($user->password);
    $role = addslashes($user->role);
    $status = addslashes($user->status ?? 'active');
    $profilePhoto = $user->profile_photo ? "'" . addslashes($user->profile_photo) . "'" : 'NULL';
    $rememberToken = $user->remember_token ? "'" . addslashes($user->remember_token) . "'" : 'NULL';
    $createdAt = $user->created_at ? "'" . $user->created_at->format('Y-m-d H:i:s') . "'" : 'NOW()';
    $updatedAt = $user->updated_at ? "'" . $user->updated_at->format('Y-m-d H:i:s') . "'" : 'NOW()';

    echo "INSERT INTO users (id, name, email, password, role, status, profile_photo, remember_token, created_at, updated_at) VALUES ";
    echo "({$user->id}, '{$name}', '{$email}', '{$password}', '{$role}', '{$status}', {$profilePhoto}, {$rememberToken}, {$createdAt}, {$updatedAt}) ";
    echo "ON CONFLICT (id) DO NOTHING;\n";
}

echo "\n-- Reset sequence for users\n";
echo "SELECT setval('users_id_seq', COALESCE((SELECT MAX(id) FROM users), 1));\n\n";

// Export PJU data
echo "-- PJU Data Export\n";
echo "-- Total records: " . PjuData::count() . "\n\n";

$pjuData = PjuData::all();
$count = 0;
foreach ($pjuData as $pju) {
    $count++;

    $fields = [
        'id' => $pju->id,
        'wilayah' => $pju->wilayah ? "'" . addslashes($pju->wilayah) . "'" : 'NULL',
        'wilayah_dishub' => $pju->wilayah_dishub ? "'" . addslashes($pju->wilayah_dishub) . "'" : 'NULL',
        'idpel' => $pju->idpel ? "'" . addslashes($pju->idpel) . "'" : 'NULL',
        'idpel_cabang' => $pju->idpel_cabang ? "'" . addslashes($pju->idpel_cabang) . "'" : 'NULL',
        'nama_jalan' => $pju->nama_jalan ? "'" . addslashes($pju->nama_jalan) . "'" : 'NULL',
        'spesifikasi_lampu' => $pju->spesifikasi_lampu ? "'" . addslashes($pju->spesifikasi_lampu) . "'" : 'NULL',
        'jumlah_lampu' => $pju->jumlah_lampu ?? 'NULL',
        'latitude' => $pju->latitude ?? 'NULL',
        'longitude' => $pju->longitude ?? 'NULL',
        'status_kdam' => $pju->status_kdam ? "'" . addslashes($pju->status_kdam) . "'" : 'NULL',
        'nomor_sr' => $pju->nomor_sr ? "'" . addslashes($pju->nomor_sr) . "'" : 'NULL',
        'photo' => $pju->photo ? "'" . addslashes($pju->photo) . "'" : 'NULL',
        'data_source' => $pju->data_source ? "'" . addslashes($pju->data_source) . "'" : 'NULL',
        'created_at' => $pju->created_at ? "'" . $pju->created_at->format('Y-m-d H:i:s') . "'" : 'NOW()',
        'updated_at' => $pju->updated_at ? "'" . $pju->updated_at->format('Y-m-d H:i:s') . "'" : 'NOW()',
    ];

    $columns = implode(', ', array_keys($fields));
    $values = implode(', ', array_values($fields));

    echo "INSERT INTO pju_data ({$columns}) VALUES ({$values}) ON CONFLICT (id) DO NOTHING;\n";

    if ($count % 1000 == 0) {
        echo "-- Exported {$count} records...\n";
    }
}

echo "\n-- Reset sequence for pju_data\n";
echo "SELECT setval('pju_data_id_seq', COALESCE((SELECT MAX(id) FROM pju_data), 1));\n\n";

echo "-- Export complete. Total PJU records: {$count}\n";
