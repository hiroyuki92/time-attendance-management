<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    protected function getTodayAttendance($status = null)
    {
        $query = Attendance::where('user_id', Auth::id())
            ->where('work_date', now()->toDateString());

        if ($status) {
            $query->where('status', $status);
        }

        return $query->first();
    }

    public function create()
    {
        $date = \Carbon\Carbon::now()->format('Y年n月j日');
        $latestAttendance = $this->getTodayAttendance();
        $isCheckedOut = $latestAttendance && $latestAttendance->status === 'left';

        $latestAttendanceStatus = null;

        $latestAttendanceStatus = !$latestAttendance ? 'no_record' : $latestAttendance->status;

        return view('user.user_attendance_create', compact('date', 'latestAttendance', 'latestAttendanceStatus','isCheckedOut'));
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
        $attendance = $this->getTodayAttendance('working');
        
        $attendance->update([
            'clock_out' => now(),
            'status' => 'left'
        ]);
        return redirect()->route('attendance.create');
    }

    public function startBreak(Request $request)
    {
        $attendance = $this->getTodayAttendance('working');

        DB::transaction(function () use ($attendance) {
            $attendance->update(['status' => 'breaking']);

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => now()
            ]);
        });

        return redirect()->route('attendance.create');
    }

    public function endBreak(Request $request)
    {
        $attendance = $this->getTodayAttendance('breaking');

        DB::transaction(function () use ($attendance) {
            $attendance->update(['status' => 'working']);

            $break = BreakTime::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->first();

            if ($break) {
                $break->update(['break_end' => now()]);
            }
        });

    return redirect()->route('attendance.create');
    }

    public function index()
    {
        $attendances = Attendance::where('user_id', Auth::id())
        ->whereMonth('work_date', now()->month)
        ->orderBy('work_date', 'asc')
        ->get();

        return view('user.user_attendance_index', compact('attendances'));
    }

    public function show()
    {
        return view('user.user_attendance_detail');
    }
}
