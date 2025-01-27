<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\AttendanceModificationRequest;
use App\Models\Attendance;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class RequestController extends Controller
{
    public function modRequest(AttendanceModificationRequest $request)
    {
        $attendance = Attendance::findOrFail($request->attendance_id);
        $workDate = $attendance->work_date->format('Y-m-d');
        $clockIn = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $request->requested_clock_in);
        $clockOut = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $request->requested_clock_out);
        
        $attendanceModRequest = AttendanceModification::create([
            'attendance_id' => $request->attendance_id,
            'requested_clock_in' => $clockIn,
            'requested_clock_out' => $clockOut,
            'reason' => $request->reason,
            'status' => AttendanceModification::STATUS_PENDING
        ]);


        if ($request->break_times) {
            foreach ($request->break_times as $breakTime) {
                $breakStart = $breakTime['requested_break_start'] !== '-' 
                    ? Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $breakTime['requested_break_start'])
                    : null;
                $breakEnd = $breakTime['requested_break_end'] !== '-' 
                    ? Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $breakTime['requested_break_end'])
                    : null;
                BreakTimeModification::create([
                    'attendance_mod_request_id' => $attendanceModRequest->id,
                    'break_times_id' => $breakTime['id'],
                    'requested_break_start' => $breakStart,
                    'requested_break_end' => $breakEnd,
                ]);
            }
        }

        return redirect()->route('attendance.index');
    }

    public function index()
    {
        return view('user.user_request_index');
    }
}
