<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceController extends Controller
{
    public function create()
    {
        $date = formatJapaneseDate();
        $latestAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('clock_in', today())
            ->first();
        return view('user.user_attendance_create', compact('date', 'latestAttendance'));
    }

    public function startWork(Request $request)
    {
        $attendance = new Attendance([
            'user_id' => Auth::id(),
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'working'
        ]);
        
        $attendance->save();
        return redirect()->route('attendance.create');
    }

    public function endWork(Request $request)
    {
    $attendance = Attendance::where('user_id', Auth::id())
        ->where('work_date', now()->toDateString())
        ->where('status', 'working')
        ->first();
        
    if ($attendance) {
        $attendance->clock_out = now();
        $attendance->status = 'left';
        $attendance->save();
    }
    return redirect()->route('attendance.create');
    }

    public function startBreak(Request $request)
    {
    $attendance = Attendance::where('user_id', Auth::id())
        ->where('work_date', now()->toDateString())
        ->where('status', 'working')
        ->first();

    if ($attendance) {
        $attendance->status = 'breaking';
        $attendance->save();

        // 休憩記録を作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()
        ]);

    }

    return redirect()->route('attendance.create');
    }

    public function endBreak(Request $request)
    {
    $attendance = Attendance::where('user_id', Auth::id())
        ->where('work_date', now()->toDateString())
        ->where('status', 'breaking')
        ->first();

    if ($attendance) {
        $attendance->status = 'working';
        $attendance->save();

        // 休憩終了時間を記録
        $break = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->first();
            
        if ($break) {
            $break->break_end = now();
            $break->save();
        }
    }

    return redirect()->route('attendance.create');
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
