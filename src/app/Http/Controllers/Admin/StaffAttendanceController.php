<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;

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

    public function staffAttendances($id)
    {
        $user = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)->get();
        return view('admin.admin_staff_attendance_list', compact('user', 'attendances'));
    }
}
