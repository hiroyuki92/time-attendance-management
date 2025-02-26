<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\AttendanceModificationRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date');
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();

        $formattedDate = $targetDate->format('Y/m/d');
        $formattedDateJP = $targetDate->format('Y年m月d日');
        $attendances = Attendance::with(['user', 'break_times'])
        ->whereDate('work_date', $targetDate->toDateString())
        ->orderBy('clock_in', 'asc')
        ->get();
        $previousDate = $targetDate->copy()->subDay()->format('Y/m/d');
        $nextDate = $targetDate->copy()->addDay()->format('Y/m/d');
        return view('admin.admin_attendance_index', compact('attendances', 'formattedDate','formattedDateJP', 'previousDate', 'nextDate'));
    }

    public function show($id)
    {
        $attendance = Attendance::findOrFail($id);
        $user = User::findOrFail($attendance->user_id);

        // 該当勤怠の修正リクエスト状況を取得
        $modRequest = AttendanceModification::where('attendance_id', $id)
            ->whereNotNull('status')
            ->first();

        $breakModRequests = [];
        if ($modRequest) {
            $breakModRequests = BreakTimeModification::where('attendance_mod_request_id', $modRequest->id)
                ->get()
                ->keyBy('break_time_id');
        }

        $isPending = $modRequest && $modRequest->status == AttendanceModification::STATUS_PENDING;

        if (session('isPending') !== null) {
            $isPending = session('isPending');
        }

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

    public function update(AttendanceModificationRequest $request)
    {
        $attendance = Attendance::findOrFail($request->attendance_id);
        $requestedWorkDate = $request->requested_work_date;


        $clockIn = Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $request->requested_clock_in);
        $clockOut = Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $request->requested_clock_out);
        $attendanceModRequest = AttendanceModification::create([
            'attendance_id' => $request->attendance_id,
            'requested_work_date' => $requestedWorkDate,
            'requested_clock_in' => $clockIn,
            'requested_clock_out' => $clockOut,
            'reason' => $request->reason,
            'status' => AttendanceModification::STATUS_APPROVED
        ]);

        if ($request->break_times) {
            foreach ($request->break_times as $index => $breakTime) {

                if (!empty($breakTime['requested_break_start']) && !empty($breakTime['requested_break_end'])) {
                BreakTimeModification::create([
                    'attendance_mod_request_id' => $attendanceModRequest->id,
                    'break_times_id' => isset($breakTime['id']) ? $breakTime['id'] : null,
                    'temp_index' => $index,
                    'requested_break_start' => Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $breakTime['requested_break_start']),
                    'requested_break_end' => Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $breakTime['requested_break_end'])
                ]);
                }
            }
        }

        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        if ($request->break_times) {
            foreach ($request->break_times as $index => $breakTime) {

            if (isset($breakTime['id'])) {
                $breakTimeModel = BreakTime::find($breakTime['id']);
                if (!empty($breakTime['requested_break_start']) && !empty($breakTime['requested_break_end'])){
                    if (isset($breakTime['id'])) {
                        $breakTimeModel = BreakTime::find($breakTime['id']);
                        if ($breakTimeModel) {
                            $breakTimeModel->update([
                                'break_start' => Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $breakTime['requested_break_start']),
                                'break_end' => Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $breakTime['requested_break_end'])
                            ]);
                        }
                        }  else {
                        $newBreakTime = BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'break_start' => $breakTime['requested_break_start'] !== '-'
                                ? Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $breakTime['requested_break_start'])
                                : null,
                            'break_end' => $breakTime['requested_break_end'] !== '-'
                                ? Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $breakTime['requested_break_end'])
                                : null,
                            ]);
                            BreakTimeModification::where('attendance_mod_request_id', $attendanceModRequest->id)
                            ->where('temp_index', $index)
                            ->update(['break_times_id' => $newBreakTime->id]);
                        }
                    }

                    $isPending = $attendanceModRequest && $attendanceModRequest->status == AttendanceModification::STATUS_PENDING;

                    return redirect()->route('admin.attendance.detail.show', $attendance->id)
                        ->with('isPending', $isPending);
                }
            }
        }
    }
}
