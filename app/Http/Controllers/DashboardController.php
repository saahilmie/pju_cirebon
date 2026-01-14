<?php

namespace App\Http\Controllers;

use App\Models\PjuData;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get total counts from database
        $totalPoints = PjuData::count();
        $totalMeterisasi = PjuData::where('kdam', 'M')->count();
        $totalAbonemen = PjuData::where('kdam', 'A')->count();
        $totalUnclear = PjuData::where(function($q) {
            $q->whereNull('kdam')->orWhereNotIn('kdam', ['M', 'A']);
        })->count();
        $totalUsers = User::count();

        $stats = [
            'total_points' => $totalPoints,
            'total_meterisasi' => $totalMeterisasi,
            'total_abonemen' => $totalAbonemen,
            'total_unclear' => $totalUnclear,
            'total_users' => $totalUsers,
        ];

        // Get regional statistics - using exact database values (uppercase)
        $regions = [
            'KAB. CIREBON' => ['name' => 'Kab. Cirebon', 'color' => '#B51CEC'],
            'KOTA CIREBON' => ['name' => 'Kota Cirebon', 'color' => '#29AAE1'], 
            'KAB. KUNINGAN' => ['name' => 'Kab. Kuningan', 'color' => '#17C353'],
            'MAJALENGKA' => ['name' => 'Majalengka', 'color' => '#FBED21'],
            'KAB. INDRAMAYU' => ['name' => 'Indramayu', 'color' => '#EB2027']
        ];
        
        $regionalStats = [];
        foreach ($regions as $dbName => $info) {
            $total = PjuData::where('nama_kabupaten', $dbName)->count();
            $mCount = PjuData::where('nama_kabupaten', $dbName)->where('kdam', 'M')->count();
            $aCount = PjuData::where('nama_kabupaten', $dbName)->where('kdam', 'A')->count();
            $unclearCount = PjuData::where('nama_kabupaten', $dbName)
                ->where(function($q) {
                    $q->whereNull('kdam')->orWhereNotIn('kdam', ['M', 'A']);
                })->count();
            
            $regionalStats[$info['name']] = [
                'total' => $total,
                'M' => $mCount,
                'A' => $aCount,
                'unclear' => $unclearCount,
                'color' => $info['color'],
                'percent' => $totalPoints > 0 ? round(($total / $totalPoints) * 100, 1) : 0,
            ];
        }

        // Get user role percentages
        $userRoles = [
            'super_admin' => $totalUsers > 0 ? round((User::where('role', 'super_admin')->count() / $totalUsers) * 100) : 0,
            'admin' => $totalUsers > 0 ? round((User::where('role', 'admin')->count() / $totalUsers) * 100) : 0,
            'employee' => $totalUsers > 0 ? round((User::where('role', 'employee')->count() / $totalUsers) * 100) : 0,
        ];

        // Get admin team
        $adminTeam = User::whereIn('role', ['super_admin', 'admin'])
            ->where('status', 'active')
            ->get()
            ->map(function ($user) {
                $names = explode(' ', $user->name);
                $user->initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                return $user;
            });

        return view('dashboard', compact('stats', 'regionalStats', 'userRoles', 'adminTeam'));
    }
}
