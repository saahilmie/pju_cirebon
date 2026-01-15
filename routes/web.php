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
    Route::delete('/api/pju-report/{id}', [PjuReportController::class, 'destroy']);

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/api/analytics/status', [AnalyticsController::class, 'getStatusData']);
    Route::get('/api/analytics/wilayah', [AnalyticsController::class, 'getWilayahData']);
    Route::get('/api/analytics/daya', [AnalyticsController::class, 'getDayaData']);
    Route::get('/api/analytics/idpel', [AnalyticsController::class, 'getIdpelAnalysis']);
    Route::get('/api/analytics/filters', [AnalyticsController::class, 'getFilterOptions']);

    // User Management (Admin only)
    Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
});
