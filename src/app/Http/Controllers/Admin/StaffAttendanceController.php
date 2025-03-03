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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;


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
                ->get();
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

    public function exportCsv($id, Request $request)
    {
        $month = $request->query('month', now()->format('Y/m'));

        $parts = explode('/', $month);
        $year = isset($parts[0]) ? $parts[0] : now()->year;
        $monthNum = isset($parts[1]) ? $parts[1] : now()->month;

        $user = User::find($id);
        $userName = $user->name;

        $attendances = Attendance::where('user_id', $id)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $monthNum)
            ->orderBy('work_date')
            ->get();

        // ファイル名
        $fileName = "{$userName}さん_{$year}_{$monthNum}.csv";

        $response = new StreamedResponse(function () use ($attendances) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩時間', '総労働時間']);

            foreach ($attendances as $attendance) {
                fputcsv($handle, [
                    $attendance->work_date ? $attendance->work_date->format('Y-m-d') : '-',
                    $attendance->clock_in ? $attendance->clock_in->format('H:i') : '-',
                    $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-',
                    $attendance->total_break_time,
                    $attendance->total_work_time,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    public function update(AttendanceModificationRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $attendance = Attendance::findOrFail($id);
            $requestedWorkDate = $request->requested_work_date;

            $clockIn = $this->createDateTimeFromString($requestedWorkDate, $request->requested_clock_in);
            $clockOut = $this->createDateTimeFromString($requestedWorkDate, $request->requested_clock_out);

            $attendanceModRequest = $this->createAttendanceModification(
                $request->id,
                $requestedWorkDate,
                $clockIn,
                $clockOut,
                $request->reason
            );
            
            // 休憩時間の修正情報を処理
            if (!empty($request->break_times)) {
                $this->processBreakTimeModifications($request->break_times, $attendanceModRequest, $requestedWorkDate);
            }

            $attendance->update([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
            ]);
            
            // 休憩時間の更新または作成
            if (!empty($request->break_times)) {
                $this->updateOrCreateBreakTimes($request->break_times, $attendance, $attendanceModRequest, $requestedWorkDate);
            }
            
            DB::commit();

            $isPending = $attendanceModRequest->status == AttendanceModification::STATUS_PENDING;
            return redirect()->route('admin.attendance.detail.show', $attendance->id)
                ->with('isPending', $isPending);
                
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('勤怠更新エラー: ' . $e->getMessage());
                return redirect()->back()->withErrors(['error' => '勤怠情報の更新中にエラーが発生しました。'])->withInput();
            }
    }

    /**
     * 日付と時間の文字列からCarbonインスタンスを作成
     */
    private function createDateTimeFromString($date, $time)
    {
        if (empty($time) || $time === '-') {
            return null;
        }
        
        return Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
    }

    /**
     * 勤怠修正リクエストを作成
     */
    private function createAttendanceModification($attendanceId, $workDate, $clockIn, $clockOut, $reason)
    {
        return AttendanceModification::create([
            'attendance_id' => $attendanceId,
            'requested_work_date' => $workDate,
            'requested_clock_in' => $clockIn,
            'requested_clock_out' => $clockOut,
            'reason' => $reason,
            'status' => AttendanceModification::STATUS_APPROVED
        ]);
    }

    /**
     * 休憩時間の修正情報を処理
     */
    private function processBreakTimeModifications($breakTimes, $attendanceModRequest, $workDate)
    {
        $existingBreakTimes = BreakTime::where('attendance_id', $attendanceModRequest->attendance_id)
            ->orderBy('break_start')
            ->get();
        
        foreach ($breakTimes as $index => $breakTime) {
            if (empty($breakTime['requested_break_start']) || empty($breakTime['requested_break_end'])) {
                continue;
            }
            
            $existingBreakTimeId = null;
            if (isset($existingBreakTimes[$index])) {
                $existingBreakTimeId = $existingBreakTimes[$index]->id;
            }
            
            BreakTimeModification::create([
                'attendance_mod_request_id' => $attendanceModRequest->id,
                'break_times_id' => $existingBreakTimeId,
                'temp_index' => $index,
                'requested_break_start' => $this->createDateTimeFromString($workDate, $breakTime['requested_break_start']),
                'requested_break_end' => $this->createDateTimeFromString($workDate, $breakTime['requested_break_end'])
            ]);
        }
    }

    /**
     * 休憩時間の更新または作成
     */
    private function updateOrCreateBreakTimes($breakTimes, $attendance, $attendanceModRequest, $workDate)
    {
        $breakTimeModifications = BreakTimeModification::where('attendance_mod_request_id', $attendanceModRequest->id)
            ->get()
            ->keyBy('temp_index');
        
        foreach ($breakTimes as $index => $breakTime) {
            if (empty($breakTime['requested_break_start']) || empty($breakTime['requested_break_end'])) {
                continue;
            }
            
            $breakStart = $this->createDateTimeFromString($workDate, $breakTime['requested_break_start']);
            $breakEnd = $this->createDateTimeFromString($workDate, $breakTime['requested_break_end']);
            
            // BreakTimeModification から既存の休憩時間IDを取得
            $existingBreakTimeId = null;
            if (isset($breakTimeModifications[$index]) && !empty($breakTimeModifications[$index]->break_times_id)) {
                $existingBreakTimeId = $breakTimeModifications[$index]->break_times_id;
            }
            
            // 既存の休憩時間IDがある場合は更新、なければ新規作成
            if ($existingBreakTimeId) {
                $breakTimeModel = BreakTime::find($existingBreakTimeId);
                
                if ($breakTimeModel) {
                    $breakTimeModel->update([
                        'break_start' => $breakStart,
                        'break_end' => $breakEnd
                    ]);
                } else {
                    $newBreakTime = BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart,
                        'break_end' => $breakEnd
                    ]);
                    
                    $breakTimeModifications[$index]->update(['break_times_id' => $newBreakTime->id]);
                }
            } else {
                $newBreakTime = BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd
                ]);
                
                if (isset($breakTimeModifications[$index])) {
                    $breakTimeModifications[$index]->update(['break_times_id' => $newBreakTime->id]);
                }
            }
        }
    }
}
