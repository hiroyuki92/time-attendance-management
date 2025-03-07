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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;



class RequestController extends Controller
{
    public function modRequest(AttendanceModificationRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $attendance = Attendance::findOrFail($request->attendance_id);
            $requestedWorkDate = Carbon::parse($request->requested_work_date)->format('Y-m-d');

            $clockIn = Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $request->requested_clock_in);
            $clockOut = Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $request->requested_clock_out);

            // 勤怠修正リクエストの作成
            $attendanceModRequest = AttendanceModification::create([
                'attendance_id' => $request->attendance_id,
                'requested_work_date' => $requestedWorkDate,
                'requested_clock_in' => $clockIn,
                'requested_clock_out' => $clockOut,
                'reason' => $request->reason,
                'status' => AttendanceModification::STATUS_PENDING
            ]);

            $breakModRequests = collect();


        if ($request->break_times) {
                foreach ($request->break_times as $index => $breakTime) {
                    if (!empty($breakTime['requested_break_start']) && !empty($breakTime['requested_break_end'])) {

                        // 既存の休憩IDがあれば取得
                        $breakTimesId = $breakTime['id'] ?? null;

                        // 休憩修正リクエストの作成
                        $breakModRequest = BreakTimeModification::create([
                            'attendance_mod_request_id' => $attendanceModRequest->id,
                            'break_times_id' => $breakTimesId,
                            'temp_index' => $index,
                            'requested_break_start' => Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $breakTime['requested_break_start']),
                            'requested_break_end' => Carbon::createFromFormat('Y-m-d H:i', $requestedWorkDate . ' ' . $breakTime['requested_break_end'])
                        ]);

                        $breakModRequests->push($breakModRequest);
                    }
                }
            }

            return redirect()->route('attendance.index')->with('breakModRequests', $breakModRequests);
        });
    }

    public function index(Request $request)
    {
        $tab = $request->input('tab', 'pending');
        $userId = Auth::id();
        
        $query = AttendanceModification::whereHas('attendance', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with('attendance.user');
        
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

}
