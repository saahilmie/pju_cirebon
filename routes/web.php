<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PjuReportController;
use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/map', function () {
        return view('map');
    })->name('map');

    // PJU Report
    Route::get('/pju-report', [PjuReportController::class, 'index'])->name('pju-report');
    Route::get('/api/pju-report/data', [PjuReportController::class, 'getData']);
    Route::post('/api/pju-report', [PjuReportController::class, 'store']);
    Route::put('/api/pju-report/{id}', [PjuReportController::class, 'update']);
    Route::post('/api/pju-report/{id}/photo', [PjuReportController::class, 'updatePhoto']);
    Route::delete('/api/pju-report/{id}', [PjuReportController::class, 'destroy']);
    Route::post('/api/pju-report/import', [PjuReportController::class, 'importCsv']);
    Route::get('/api/pju-report/export', [PjuReportController::class, 'exportExcel']);

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/api/analytics/status', [AnalyticsController::class, 'getStatusData']);
    Route::get('/api/analytics/wilayah', [AnalyticsController::class, 'getWilayahData']);
    Route::get('/api/analytics/daya', [AnalyticsController::class, 'getDayaData']);
    Route::get('/api/analytics/idpel', [AnalyticsController::class, 'getIdpelAnalysis']);
    Route::get('/api/analytics/filters', [AnalyticsController::class, 'getFilterOptions']);

    // User Management (Admin only)
    Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);

    // Profile Settings
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/avatar', [App\Http\Controllers\ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

    // Photo Upload (Bulk)
    Route::get('/photo-upload', [App\Http\Controllers\PhotoUploadController::class, 'index'])->name('photo-upload');
    Route::post('/api/photo-upload/analyze', [App\Http\Controllers\PhotoUploadController::class, 'analyze']);
    Route::post('/api/photo-upload/process', [App\Http\Controllers\PhotoUploadController::class, 'process']);
});

// EMERGENCY CLEANUP ROUTE (Secret URL)
Route::get('/cleanup-duplicates-now-please', function () {
    try {
        // Increase memory limit for this heavy operation
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $deleted = \Illuminate\Support\Facades\DB::delete("
            DELETE FROM pju_data 
            WHERE id IN (
                SELECT id FROM (
                    SELECT id,
                    ROW_NUMBER() OVER (PARTITION BY idpel ORDER BY id DESC) as rn
                    FROM pju_data
                    WHERE (kdam IS NULL OR kdam = '' OR kdam NOT IN ('M', 'A'))
                ) t
                WHERE t.rn > 1
            )
        ");
        return "<h1>CLEANUP SUCCESS ✅</h1><p>Deleted Records: <strong>$deleted</strong></p><p>Current Total Data: " . \App\Models\PjuData::count() . "</p><br><a href='/dashboard'>Back to Dashboard</a>";
    } catch (\Exception $e) {
        return "<h1>ERROR ❌</h1><p>" . $e->getMessage() . "</p>";
    }
});
