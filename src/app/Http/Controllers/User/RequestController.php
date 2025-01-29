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
                BreakTimeModification::create([
                    'attendance_mod_request_id' => $attendanceModRequest->id,
                    'break_times_id' => $breakTime['id'],
                    'requested_break_start' => $breakTime['requested_break_start'] !== '-' 
                        ? Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $breakTime['requested_break_start'])
                        : null,
                    'requested_break_end' => $breakTime['requested_break_end'] !== '-' 
                        ? Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $breakTime['requested_break_end'])
                        : null,
                ]);
            }
        }

        return redirect()->route('attendance.index');
    }

    public function index(Request $request)
{
    $tab = $request->input('tab', 'pending'); // デフォルトは'pending'
    $userId = Auth::id();
    
    // クエリのベース部分を作成
    $query = AttendanceModification::whereHas('attendance', function ($query) use ($userId) {
        $query->where('user_id', $userId);
    })->with('attendance.user');
    
    // タブに応じてステータスを切り替え
    if ($tab === 'pending') {
        $requests = $query->where('status', AttendanceModification::STATUS_PENDING)->get();
        $status_label = '承認待ち';
    } else {
        $requests = $query->where('status', AttendanceModification::STATUS_APPROVED)->get();
        $status_label = '承認済み';
    }
    
    // ステータスラベルを追加
    $requests = $requests->map(function ($request) use ($status_label) {
        $request->status_label = $status_label;
        return $request;
    });

    return view('user.user_request_index', compact('requests', 'tab'));
}


    /* public function index()
    {
        $tab = $request->input('pending', 'approved');
        $userId = Auth::id();
        
        // 承認中のデータを取得
        $pendingRequests = AttendanceModification::whereHas('attendance', function ($query) use ($userId) {
            $query->where('user_id', $userId);
            })->where('status', AttendanceModification::STATUS_PENDING)
            ->with('attendance.user')
            ->get();

        // 承認済みのデータを取得
        $approvedRequests = AttendanceModification::whereHas('attendance', function     ($query) use ($userId) {
            $query->where('user_id', $userId);
            })->where('status', AttendanceModification::STATUS_APPROVED)
            ->with('attendance.user')
            ->get();
        
            $pendingRequests = $pendingRequests->map(function ($request) {
                $request->status_label = '承認待ち';
                return $request;
        });

        $approvedRequests = $approvedRequests->map(function ($request) {
            $request->status_label = '承認済み';
            return $request;
        });

        return view('user.user_request_index', compact('pendingRequests', 'approvedRequests'));
    } */

    public function show($id)
    {
        $userId = Auth::id();

        $attendance = Attendance::with(['user', 'break_times'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // 該当勤怠の修正リクエスト状況を取得
        $modRequest = AttendanceModification::where('attendance_id', $id)
            ->where('status', AttendanceModification::STATUS_PENDING)
            ->first();

        $isPending = $modRequest ? true : false;

        return view('user.user_attendance_detail', compact('attendance', 'isPending'));
    }
}
