<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\PjuData;

Route::get('/pju-markers', function (Request $request) {
    $limit = $request->get('limit', 500);
    
    $points = PjuData::whereNotNull('koordinat_x')
        ->whereNotNull('koordinat_y')
        ->select('idpel', 'koordinat_x', 'koordinat_y', 'kdam', 'nama_kabupaten', 'nama')
        ->limit($limit)
        ->get();
    
    return response()->json($points);
});
