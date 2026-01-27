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

    // Photo filter - default to only with photos for performance
    $withPhoto = $request->get('withPhoto', '1');

    $query = PjuData::whereNotNull('koordinat_x')
        ->whereNotNull('koordinat_y');

    // Only filter by photo if withPhoto=1
    if ($withPhoto === '1') {
        $query->whereNotNull('photo')
            ->where('photo', '!=', '');
    }

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

// Get region boundaries based on marker coordinates for dynamic polygons
Route::get('/region-bounds', function () {
    $regions = PjuData::whereNotNull('nama_kabupaten')
        ->whereNotNull('koordinat_x')
        ->whereNotNull('koordinat_y')
        ->where('koordinat_x', '!=', 0)
        ->where('koordinat_y', '!=', 0)
        ->select('nama_kabupaten', 'koordinat_x', 'koordinat_y')
        ->get()
        ->groupBy('nama_kabupaten')
        ->map(function ($points, $region) {
            $coords = $points->map(function ($p) {
                return [(float) $p->koordinat_x, (float) $p->koordinat_y];
            })->values()->toArray();

            return [
                'name' => $region,
                'points' => $coords,
                'count' => count($coords)
            ];
        })
        ->values();

    return response()->json($regions);
});
