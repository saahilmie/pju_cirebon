<?php

namespace App\Http\Controllers;

use App\Models\PjuData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PjuReportController extends Controller
{
    public function index()
    {
        return view('pju-report');
    }

    public function getData(Request $request)
    {
        $limit = $request->get('limit', 100);

        $data = PjuData::select([
            'id',
            'idpel',
            'nama',
            'namapnj',
            'rt',
            'rw',
            'tarif',
            'daya',
            'jenislayanan',
            'nomor_meter_kwh',
            'nomor_gardu',
            'nomor_jurusan_tiang',
            'nama_gardu',
            'nomor_meter_prepaid',
            'koordinat_x',
            'koordinat_y',
            'kdam',
            'nama_kabupaten',
            'nama_kecamatan',
            'nama_kelurahan',
            'photo',
        ])
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->jenis_layanan = $item->jenislayanan ?: ($item->nomor_meter_prepaid ? 'PRABAYAR' : 'PASKABAYAR');
                return $item;
            });

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'idpel' => 'required|string|max:20',
            'nama' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png|max:20480',
        ]);

        $data = $request->except('photo');

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('pju-photos', 'public');
            $data['photo'] = $path;
        }

        $pju = PjuData::create($data);

        return response()->json(['success' => true, 'message' => 'Data successfully added', 'data' => $pju]);
    }

    public function update(Request $request, $id)
    {
        $pju = PjuData::findOrFail($id);

        $request->validate([
            'idpel' => 'required|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png|max:20480',
        ]);

        $data = $request->except('photo');

        if ($request->hasFile('photo')) {
            if ($pju->photo) {
                Storage::disk('public')->delete($pju->photo);
            }
            $path = $request->file('photo')->store('pju-photos', 'public');
            $data['photo'] = $path;
        }

        $pju->update($data);

        return response()->json(['success' => true, 'message' => 'Data successfully updated', 'data' => $pju]);
    }

    public function destroy($id)
    {
        $pju = PjuData::findOrFail($id);

        if ($pju->photo) {
            Storage::disk('public')->delete($pju->photo);
        }

        $pju->delete();

        return response()->json(['success' => true, 'message' => 'Data successfully deleted']);
    }
}
