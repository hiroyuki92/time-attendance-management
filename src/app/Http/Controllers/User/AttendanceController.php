<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function create()
    {
        $date = formatJapaneseDate();
        return view('user.user_attendance_create', compact('date'));
    }

    public function index()
    {
        return view('user.user_attendance_index');
    }

    public function show()
    {
        return view('user.user_attendance_detail');
    }
}
