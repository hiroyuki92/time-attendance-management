<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\AttendanceModificationRequest;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceModification;
use App\Models\BreakTimeModification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminRequestController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'pending');

        $query = AttendanceModification::query()
            ->with('attendance.user')
            ->join('attendances', 'attendance_mod_requests.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.work_date', 'asc')
            ->select('attendance_mod_requests.*');

        if ($tab === 'pending') {
            $requests = $query->where('attendance_mod_requests.status', AttendanceModification::STATUS_PENDING)->get();
            $status_label = '承認待ち';
        } else {
            $requests = $query->where('attendance_mod_requests.status', AttendanceModification::STATUS_APPROVED)->get();
            $status_label = '承認済み';
        }

        $requests = $query->get()->map(function ($request) use ($status_label) {
        $request->status_label = $status_label;
        return $request;
        });

        return view('admin.admin_request_index', compact('requests', 'tab', 'status_label'));
    }

    public function show($attendance_correct_request)
    {
        $modRequest = AttendanceModification::findOrFail($attendance_correct_request);
        $attendance = $modRequest->attendance;
        $user = User::findOrFail($attendance->user_id);

        $breakModRequests = BreakTimeModification::where('attendance_mod_request_id', $modRequest->id)
            ->get()
            ->keyBy('break_time_id');

        $isPending = $modRequest->status == AttendanceModification::STATUS_PENDING;

        return view('admin.admin_request_approval', compact('user', 'attendance', 'modRequest', 'breakModRequests', 'isPending'));
    }

    public function approve($attendance_correct_request)
    {
        $modRequest = AttendanceModification::findOrFail($attendance_correct_request);

        $modRequest->status = AttendanceModification::STATUS_APPROVED;

        $modRequest->save();

        return redirect()->route('admin.requests.show', ['attendance_correct_request' => $attendance_correct_request]);
    }
}
