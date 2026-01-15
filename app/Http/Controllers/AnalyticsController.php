<?php

namespace App\Http\Controllers;

use App\Models\PjuData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('analytics');
    }

    // Get Status Meter distribution (Meterisasi, Abonemen, Unclear)
    public function getStatusData(Request $request)
    {
        $query = PjuData::query();

        // Apply filters
        if ($request->filled('wilayah')) {
            $query->where('nama_kabupaten', $request->wilayah);
        }
        if ($request->filled('daya')) {
            $query->where('daya', $request->daya);
        }

        $data = $query->selectRaw("
            SUM(CASE WHEN kdam = 'M' THEN 1 ELSE 0 END) as meterisasi,
            SUM(CASE WHEN kdam = 'A' THEN 1 ELSE 0 END) as abonemen,
            SUM(CASE WHEN kdam IS NULL OR (kdam != 'M' AND kdam != 'A') THEN 1 ELSE 0 END) as unclear,
            COUNT(*) as total
        ")->first();

        return response()->json([
            'labels' => ['Meterisasi', 'Abonemen', 'Unclear'],
            'data' => [(int) $data->meterisasi, (int) $data->abonemen, (int) $data->unclear],
            'total' => (int) $data->total,
            'colors' => ['#17C353', '#FBED21', '#EB2027']
        ]);
    }

    // Get PJU count per Wilayah (Kabupaten/Kota)
    public function getWilayahData(Request $request)
    {
        $query = PjuData::query();

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'unclear') {
                $query->where(function ($q) {
                    $q->whereNull('kdam')->orWhere(function ($q2) {
                        $q2->where('kdam', '!=', 'M')->where('kdam', '!=', 'A');
                    });
                });
            } else {
                $query->where('kdam', $request->status);
            }
        }
        if ($request->filled('daya')) {
            $query->where('daya', $request->daya);
        }

        $data = $query->select('nama_kabupaten', DB::raw('COUNT(*) as count'))
            ->whereNotNull('nama_kabupaten')
            ->groupBy('nama_kabupaten')
            ->orderByDesc('count')
            ->get();

        // Define colors for regions
        $colorMap = [
            'KAB. CIREBON' => '#B51CEC',
            'KOTA CIREBON' => '#29AAE1',
            'KAB. INDRAMAYU' => '#EB2027',
            'MAJALENGKA' => '#FBED21',
            'KAB. KUNINGAN' => '#17C353',
        ];

        $labels = [];
        $counts = [];
        $colors = [];

        foreach ($data as $item) {
            $labels[] = $item->nama_kabupaten;
            $counts[] = (int) $item->count;
            $colors[] = $colorMap[$item->nama_kabupaten] ?? '#6B7280';
        }

        return response()->json([
            'labels' => $labels,
            'data' => $counts,
            'colors' => $colors
        ]);
    }

    // Get Daya distribution
    public function getDayaData(Request $request)
    {
        $query = PjuData::query();

        // Apply filters
        if ($request->filled('wilayah')) {
            $query->where('nama_kabupaten', $request->wilayah);
        }
        if ($request->filled('status')) {
            if ($request->status === 'unclear') {
                $query->where(function ($q) {
                    $q->whereNull('kdam')->orWhere(function ($q2) {
                        $q2->where('kdam', '!=', 'M')->where('kdam', '!=', 'A');
                    });
                });
            } else {
                $query->where('kdam', $request->status);
            }
        }

        $data = $query->select('daya', DB::raw('COUNT(*) as count'))
            ->whereNotNull('daya')
            ->groupBy('daya')
            ->orderBy('daya')
            ->get();

        return response()->json([
            'labels' => $data->pluck('daya')->map(fn($d) => $d . ' VA')->toArray(),
            'data' => $data->pluck('count')->map(fn($c) => (int) $c)->toArray(),
            'rawLabels' => $data->pluck('daya')->toArray()
        ]);
    }

    // Get IDPEL analysis - potential anomalies (many PJU with low daya)
    public function getIdpelAnalysis(Request $request)
    {
        $query = PjuData::query();

        // Apply filters
        if ($request->filled('wilayah')) {
            $query->where('nama_kabupaten', $request->wilayah);
        }
        if ($request->filled('status')) {
            if ($request->status === 'unclear') {
                $query->where(function ($q) {
                    $q->whereNull('kdam')->orWhere(function ($q2) {
                        $q2->where('kdam', '!=', 'M')->where('kdam', '!=', 'A');
                    });
                });
            } else {
                $query->where('kdam', $request->status);
            }
        }

        // Find IDPELs with more than 3 PJU
        $data = $query->select('idpel', 'daya', 'nama_kabupaten', 'kdam', DB::raw('COUNT(*) as pju_count'))
            ->groupBy('idpel', 'daya', 'nama_kabupaten', 'kdam')
            ->having('pju_count', '>', 3)
            ->orderByDesc('pju_count')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                // Mark as potential anomaly if daya <= 900 but pju_count > 3
                $item->is_anomaly = ($item->daya <= 900 && $item->pju_count > 3);
                $item->status = $item->kdam === 'M' ? 'Meterisasi' : ($item->kdam === 'A' ? 'Abonemen' : 'Unclear');
                return $item;
            });

        return response()->json([
            'data' => $data,
            'total_anomalies' => $data->where('is_anomaly', true)->count()
        ]);
    }

    // Get filter options
    public function getFilterOptions()
    {
        $wilayah = PjuData::select('nama_kabupaten')
            ->whereNotNull('nama_kabupaten')
            ->distinct()
            ->pluck('nama_kabupaten');

        $daya = PjuData::select('daya')
            ->whereNotNull('daya')
            ->distinct()
            ->orderBy('daya')
            ->pluck('daya');

        return response()->json([
            'wilayah' => $wilayah,
            'daya' => $daya,
            'status' => [
                ['value' => 'M', 'label' => 'Meterisasi'],
                ['value' => 'A', 'label' => 'Abonemen'],
                ['value' => 'unclear', 'label' => 'Unclear']
            ]
        ]);
    }
}
