<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $date = formatJapaneseDate();  // ヘルパー関数を使用
        return view('user.user_attendance_create', compact('date'));
    }
}
