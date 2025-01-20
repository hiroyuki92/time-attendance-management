<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffAttendanceController extends Controller
{
    public function index()
    {
        return view('admin.admin_attendance_index');
    }

    public function show()
    {
        return view('admin.admin_attendance_detail');
    }

    public function staffAttendances()
    {
        return view('admin.admin_staff_attendance_list');
    }
}
