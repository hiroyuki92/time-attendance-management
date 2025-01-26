<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceModRequest;
use App\Models\BreakTimeModRequest;

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

    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $startOfMonth = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $attendances = Attendance::where('user_id', Auth::id())
        ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
        ->orderBy('work_date', 'asc')
        ->get();

        $previousMonth = $startOfMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $startOfMonth->copy()->addMonth()->format('Y-m');

        $currentMonth = $startOfMonth->format('Y/m');

        return view('user.user_attendance_index', compact('attendances', 'currentMonth', 'previousMonth', 'nextMonth', 'month'));
    }

    public function show($id)
    {
        $userId = Auth::id();

        // ログインユーザーに紐付く勤怠データを取得
        $attendance = Attendance::with(['user', 'break_times'])
            ->where('id', $id) // 特定の勤怠レコード
            ->where('user_id', $userId) // ログインユーザーに限定
            ->firstOrFail();

        // 該当勤怠の修正リクエスト状況を取得
        $modRequest = AttendanceModRequest::where('attendance_id', $id)
            ->where('status', AttendanceModRequest::STATUS_PENDING)
            ->first();

        // 申請中かどうかを判定
        $isPending = $modRequest ? true : false;

        return view('user.user_attendance_detail', compact('attendance', 'isPending'));
    }

    public function modRequest(Request $request)
    {
        $attendanceModRequest = AttendanceModRequest::create([
            'attendance_id' => $request->attendance_id,
            'requested_clock_in' => $request->clock_in,
            'requested_clock_out' => $request->clock_out,
            'reason' => $request->reason,
            'status' => AttendanceModRequest::STATUS_PENDING
        ]);


        if ($request->break_times) {
            foreach ($request->break_times as $breakTime) {
                BreakTimeModRequest::create([
                    'attendance_mod_request_id' => $attendanceModRequest->id,
                    'break_times_id' => $breakTime['id'],
                    'requested_break_start' => $breakTime['break_start'] ?? null,
                    'requested_break_end' => $breakTime['break_end'] ?? null,
                ]);
            }
        }

        return redirect()->route('attendance.index');
    }
}
