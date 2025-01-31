<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;

class StaffAttendanceController extends Controller
{
    public function index()
    {
        return view('admin.admin_attendance_index');
    }

    public function show($id)
    {
        $attendance = Attendance::findOrFail($id);
        $user = User::findOrFail($attendance->user_id);

        // 該当勤怠の修正リクエスト状況を取得
        $modRequest = AttendanceModification::where('attendance_id', $id)
            ->where('status', AttendanceModification::STATUS_PENDING)
            ->first();
        
        // 休憩時間の修正リクエストを取得
        $breakModRequests = [];
        if ($modRequest) {
            $breakModRequests = BreakTimeModification::where('attendance_mod_request_id', $modRequest->id)
                ->get()
                ->keyBy('break_time_id');
        }

        // 申請中かどうかを判定
        $isPending = $modRequest ? true : false;

        return view('admin.admin_attendance_detail', compact('user', 'attendance', 'modRequest', 'breakModRequests', 'isPending'));

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
