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

    // Additional filters
    $region = $request->get('region');
    $status = $request->get('status');
    $search = $request->get('search');

    $query = PjuData::whereNotNull('koordinat_x')
        ->whereNotNull('koordinat_y');

    // Only filter by photo if withPhoto=1
    if ($withPhoto === '1') {
        $query->whereNotNull('photo')
            ->where('photo', '!=', '');
    }

    // Filter by region (nama_kabupaten)
    if ($region && $region !== 'null') {
        $query->where('nama_kabupaten', $region);
    }

    // Filter by status (kdam)
    if ($status && $status !== 'null') {
        if ($status === 'unclear') {
            $query->where(function ($q) {
                $q->whereNull('kdam')
                    ->orWhere('kdam', '')
                    ->orWhereNotIn('kdam', ['M', 'A']);
            });
        } else {
            $query->where('kdam', $status);
        }
    }

    // Filter by IDPEL search
    if ($search && $search !== '') {
        $query->where('idpel', 'LIKE', "%{$search}%");
    }

    // Filter by viewport if bounds are provided
    if ($minLat && $maxLat && $minLng && $maxLng) {
        $query->whereBetween('koordinat_x', [(float) $minLat, (float) $maxLat])
            ->whereBetween('koordinat_y', [(float) $minLng, (float) $maxLng]);
    }

    // --- PRIORITIZATION & LIMIT FIX ---
    // Ensure Meterisasi (M) and Abonemen (A) are returned FIRST
    $query->orderByRaw("CASE WHEN kdam IN ('M', 'A') THEN 1 ELSE 2 END");

    // Secondary sort by ID desc
    $query->orderBy('id', 'desc');

    // Override limit if it's the default frontend request (5000)
    // Increase to 25000 to show more data
    if ($limit == 5000) {
        $limit = 25000;
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
        'is_idpel_main',
        'update_color_marker'
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
            'is_idpel_main',
            'update_color_marker'
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
    // Define valid coordinate bounds for Cirebon area
    // Latitude: -8 to -6 (Jawa Barat region)
    // Longitude: 106 to 110 (area sekitar Cirebon-Indramayu-Kuningan)
    $minLat = -8.5;
    $maxLat = -5.5;
    $minLng = 106;
    $maxLng = 110;

    $regions = PjuData::whereNotNull('nama_kabupaten')
        ->whereNotNull('koordinat_x')
        ->whereNotNull('koordinat_y')
        ->where('koordinat_x', '!=', 0)
        ->where('koordinat_y', '!=', 0)
        // Filter valid coordinates within Cirebon area bounds
        ->whereBetween('koordinat_x', [$minLat, $maxLat])
        ->whereBetween('koordinat_y', [$minLng, $maxLng])
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
