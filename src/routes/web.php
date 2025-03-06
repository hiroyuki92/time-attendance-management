<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\RequestController;
use App\Http\Controllers\User\Auth\RegisteredUserController;
use App\Http\Controllers\User\Auth\UserLoginController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;


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
Route::get('/', function () {
    return response('Hello, world!', 200);
});

// メール認証関連のルート
Route::get('/email/verify', [RegisteredUserController::class, 'showVerificationNotice'])
    ->middleware('auth')
    ->name('verification.notice');

Route::post('/email/resend', [RegisteredUserController::class, 'resendVerificationEmail'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.resend');


// 管理者認証ルート
Route::prefix('admin')->name('admin.')->group(function () {
    // 未認証の管理者用ルート
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'index']);
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
    });

    // 認証済み管理者用ルート
    Route::middleware(['auth', 'admin'])->group(function () {
        // スタッフ管理
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('admin_logout');
        
        // 勤怠管理
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/list', [StaffAttendanceController::class, 'index'])->name('list');
            Route::get('/{id}', [StaffAttendanceController::class, 'show'])->name('detail.show');
            Route::put('/{id}', [StaffAttendanceController::class, 'update'])->name('update');
            Route::get('/staff/{id}', [StaffAttendanceController::class, 'staffAttendances'])->name('staff.show');
            Route::get('/export/{id}', [StaffAttendanceController::class, 'exportCsv'])->name('export');
        });

        // 打刻修正申請
        Route::prefix('stamp_correction_request')->name('requests.')->group(function () {
            Route::get('/list', [AdminRequestController::class, 'index'])->name('index');
            Route::get('/approve/{attendance_correct_request}', [AdminRequestController::class, 'show'])->name('show');
            Route::post('/approve/{attendance_correct_request}', [AdminRequestController::class, 'approve'])->name('approve');
        });
    });
});

// 一般ユーザー用ルート
    // 未認証のユーザー用ルート
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [UserLoginController::class, 'index']);
    Route::post('/login', [UserLoginController::class, 'login'])->name('user_login');
    Route::post('/logout', [UserLoginController::class, 'logout'])->name('logout');


Route::middleware('auth','verified')->group(function () {
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
    Route::prefix('stamp_correction_request')->name('requests.')->group(function () {
        Route::get('/list', [RequestController::class, 'index'])->name('index');
    });

});

