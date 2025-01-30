<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\RequestController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 管理者認証ルート
Route::prefix('admin')->name('admin.')->group(function () {
    // 未認証の管理者用ルート
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'index']);
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
    });

    // 認証済み管理者用ルート
    Route::middleware('auth')->group(function () {
        // スタッフ管理
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        
        // 勤怠管理
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/list', [StaffAttendanceController::class, 'index'])->name('list');
            Route::get('/id', [StaffAttendanceController::class, 'show'])->name('detail.show');
            Route::get('/staff/id', [StaffAttendanceController::class, 'staffAttendances'])->name('staff.show');
        });

        // 打刻修正申請
        Route::prefix('stamp-correction')->name('requests.')->group(function () {
            Route::get('/list', [AdminRequestController::class, 'index'])->name('index');
            Route::get('/approve', [AdminRequestController::class, 'show'])->name('show');
        });
    });
});

// 一般ユーザー用ルート
Route::middleware('auth')->group(function () {
    // 勤怠管理
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'create'])->name('create');
        Route::post('/start', [AttendanceController::class, 'startWork'])->name('start');
        Route::post('/end', [AttendanceController::class, 'endWork'])->name('end');
        Route::post('/break/start', [AttendanceController::class, 'startBreak'])->name('break.start');
        Route::post('/break/end', [AttendanceController::class, 'endBreak'])->name('break.end');
        Route::get('/list', [AttendanceController::class, 'index'])->name('index');
        Route::get('/{id}', [AttendanceController::class, 'show'])->name('show');
        Route::post('/mod-request', [RequestController::class, 'modRequest'])->name('mod_request');
    });

    // 打刻修正申請
    Route::prefix('stamp-correction')->name('requests.')->group(function () {
        Route::get('/list', [RequestController::class, 'index'])->name('index');
    });

});

