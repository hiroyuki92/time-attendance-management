<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
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

    public function staffAttendances(Request $request, $id)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $startOfMonth = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $previousMonth = $startOfMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $startOfMonth->copy()->addMonth()->format('Y-m');
        $currentMonth = $startOfMonth->format('Y/m');

        $user = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)
        ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
        ->orderBy('work_date', 'asc')
        ->get();
        return view('admin.admin_staff_attendance_list', compact('user', 'attendances', 'currentMonth', 'previousMonth', 'nextMonth', 'month'));
    }
}
