<?php

use App\Http\Controllers\AttendanceApiController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeSyncController;
use App\Http\Middleware\AttendanceApiKey;
use Illuminate\Support\Facades\Route;

// ── Root → Dashboard ─────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('dashboard'));

// ── Professional Dashboard ───────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ── AJAX APIs ────────────────────────────────────────────────────────────────
Route::prefix('api/attendance')->name('api.attendance.')->group(function () {
    Route::get('grid',              [DashboardController::class, 'gridApi'])->name('grid');
    Route::get('stats',             [DashboardController::class, 'statsApi'])->name('stats');
    Route::get('timeline/{code}',   [DashboardController::class, 'timelineApi'])->name('timeline');
    Route::get('report',            [DashboardController::class, 'reportApi'])->name('report');
});

Route::get('/api/sync/status', [DashboardController::class, 'syncStatusApi'])->name('api.sync.status');
Route::post('/api/employees/sync', [EmployeeSyncController::class, 'sync'])->name('api.employees.sync');

// ── Attendance API (consumed by HRMS) ─────────────────────────────────────────
Route::middleware(AttendanceApiKey::class)->prefix('api/attendance')->name('api.attendance.')->group(function () {
    Route::get('today',                  [AttendanceApiController::class, 'today'])->name('today');
    Route::get('date/{date}',            [AttendanceApiController::class, 'byDate'])->name('byDate');
    Route::get('employee/{code}',        [AttendanceApiController::class, 'byEmployee'])->name('byEmployee');
});

// ── Admin Correction ──────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('sessions',                  [DashboardController::class, 'adminSessions'])->name('sessions');
    Route::patch('sessions/{id}',           [DashboardController::class, 'adminUpdateSession'])->name('sessions.update');
    Route::post('sessions/recalculate',     [DashboardController::class, 'adminRecalculate'])->name('sessions.recalculate');
    Route::post('sessions/recalculate-all', [DashboardController::class, 'adminRecalculateAll'])->name('sessions.recalculate_all');
});

// ── Reports ───────────────────────────────────────────────────────────────────
Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');

// ── Legacy attendance routes (kept for compatibility) ─────────────────────────
Route::get('/attendance',       [AttendanceController::class, 'index'])->name('attendance.index');
Route::get('/attendance/feed',  [AttendanceController::class, 'feed'])->name('attendance.feed');
Route::get('/attendance/debug', [AttendanceController::class, 'debug'])->name('attendance.debug');
Route::get('/attendance/raw-logs', [AttendanceController::class, 'rawLogs'])->name('attendance.rawlogs');

Route::match(['GET', 'POST'], '/test-attendance',       [AttendanceController::class, 'testAttendance'])->name('attendance.test');
Route::match(['GET', 'POST'], '/test-attendance/bulk',  [AttendanceController::class, 'testAttendanceBulk'])->name('attendance.test.bulk');
