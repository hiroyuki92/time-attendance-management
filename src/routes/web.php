<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\User\AttendanceController;

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
    return view('welcome');
});
Route::get('/admin/login', [AuthenticatedSessionController::class, 'index'])->name('admin.login');
Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
Route::get('/attendance/id', [AttendanceController::class, 'show'])->name('attendance.show');

Route::get('/admin/attendance/list', [StaffAttendanceController::class, 'index'])->name('staff.attendance.list');
Route::get('/admin/attendance/id', [StaffAttendanceController::class, 'show'])->name('staff.attendance.detail.show');
Route::get('/admin/staff/list', [StaffController::class, 'index'])->name('staff.index');
Route::get('/admin/attendance/staff/id', [StaffAttendanceController::class, 'staffAttendances'])->name('staff.attendance.show');
Route::get('/admin/stamp_correction_request/list', [AdminRequestController::class, 'index'])->name('admin.requests.index');

