<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\PjuData;

Route::get('/pju-markers', function (Request $request) {
    $limit = $request->get('limit', 1000);

    // Viewport bounds for optimized loading
    $minLat = $request->get('minLat');
    $maxLat = $request->get('maxLat');
    $minLng = $request->get('minLng');
    $maxLng = $request->get('maxLng');

    $query = PjuData::whereNotNull('koordinat_x')
        ->whereNotNull('koordinat_y')
        ->whereNotNull('photo') // Only load markers with photos
        ->where('photo', '!=', '');

    // Filter by viewport if bounds are provided
    if ($minLat && $maxLat && $minLng && $maxLng) {
        $query->whereBetween('koordinat_x', [(float) $minLat, (float) $maxLat])
            ->whereBetween('koordinat_y', [(float) $minLng, (float) $maxLng]);
    }

    $points = $query->select([
        'idpel',
        'nama',
        'namapnj',
        'rt',
        'rw',
        'tarif',
        'daya',
        'kdam',
        'nama_kabupaten',
        'nama_kecamatan',
        'nama_kelurahan',
        'jenislayanan',
        'nomor_meter_kwh',
        'nomor_meter_prepaid',
        'nomor_gardu',
        'nama_gardu',
        'nomor_jurusan_tiang',
        'koordinat_x',
        'koordinat_y',
        'photo',
        'is_idpel_main'
    ])
        ->limit($limit)
        ->get();

    return response()->json($points);
});

// Server-side search for map markers by IDPEL
Route::get('/pju-markers/search', function (Request $request) {
    $search = $request->get('q', '');

    if (empty($search)) {
        return response()->json([]);
    }

    $points = PjuData::whereNotNull('koordinat_x')
        ->whereNotNull('koordinat_y')
        ->where('idpel', 'LIKE', "%{$search}%")
        ->select([
            'idpel',
            'nama',
            'namapnj',
            'rt',
            'rw',
            'tarif',
            'daya',
            'kdam',
            'nama_kabupaten',
            'nama_kecamatan',
            'nama_kelurahan',
            'jenislayanan',
            'nomor_meter_kwh',
            'nomor_meter_prepaid',
            'nomor_gardu',
            'nama_gardu',
            'nomor_jurusan_tiang',
            'koordinat_x',
            'koordinat_y',
            'photo',
            'is_idpel_main'
        ])
        ->limit(100)
        ->get();

    return response()->json($points);
});

Route::get('/pju-data', function (Request $request) {
    $limit = $request->get('limit', 100);

    $data = PjuData::select('id', 'idpel', 'nama', 'namapnj', 'rt', 'rw', 'tarif', 'daya', 'kdam', 'nama_kabupaten', 'no_meter')
        ->limit($limit)
        ->get()
        ->map(function ($item) {
            $item->jenis_layanan = $item->no_meter ? 'PRABAYAR' : 'PASKABAYAR';
            return $item;
        });

    return response()->json(['data' => $data]);
});
