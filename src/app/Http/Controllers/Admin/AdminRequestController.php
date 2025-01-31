<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\AttendanceModificationRequest;
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

        return view('admin.admin_request_index', compact('requests', 'tab'));
    }

    public function show()
    {
        return view('admin.admin_request_approval');
    }
}
