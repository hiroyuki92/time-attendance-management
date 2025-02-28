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

    public function exportCsv($id, Request $request)
    {
        // デフォルトは現在の年月
        $month = $request->query('month', now()->format('Y/m'));
        
        // 「2025/02」形式の文字列を解析して年と月を取得
        $parts = explode('/', $month);
        $year = isset($parts[0]) ? $parts[0] : now()->year;
        $monthNum = isset($parts[1]) ? $parts[1] : now()->month;

        $user = User::find($id);
        $userName = $user->name;
        
        // この年月の勤怠データを取得
        $attendances = Attendance::where('user_id', $id)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $monthNum)
            ->orderBy('work_date')
            ->get();

        // ファイル名用にフォーマット
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
        
        // 勤怠記録を取得
        $attendance = Attendance::findOrFail($id);
        $requestedWorkDate = $request->requested_work_date;
        
        // 出退勤時間の作成
        $clockIn = $this->createDateTimeFromString($requestedWorkDate, $request->requested_clock_in);
        $clockOut = $this->createDateTimeFromString($requestedWorkDate, $request->requested_clock_out);
        
        // 基本的な勤怠修正リクエストの作成
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
        
        // 勤怠記録の更新
        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);
        
        // 休憩時間の更新または作成
        if (!empty($request->break_times)) {
            $this->updateOrCreateBreakTimes($request->break_times, $attendance, $attendanceModRequest, $requestedWorkDate);
        }
        
        DB::commit();
        
        // リダイレクト
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
    foreach ($breakTimes as $index => $breakTime) {
        if (empty($breakTime['requested_break_start']) || empty($breakTime['requested_break_end'])) {
            continue;
        }
        
        BreakTimeModification::create([
            'attendance_mod_request_id' => $attendanceModRequest->id,
            'break_times_id' => $breakTime['id'] ?? null,
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
    foreach ($breakTimes as $index => $breakTime) {
        // 休憩時間が空の場合はスキップ
        if (empty($breakTime['requested_break_start']) || empty($breakTime['requested_break_end'])) {
            continue;
        }
        
        $breakStart = $this->createDateTimeFromString($workDate, $breakTime['requested_break_start']);
        $breakEnd = $this->createDateTimeFromString($workDate, $breakTime['requested_break_end']);
        
        if (!empty($breakTime['id'])) {
            // 既存の休憩時間を更新
            $breakTimeModel = BreakTime::find($breakTime['id']);
            if ($breakTimeModel) {
                $breakTimeModel->update([
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd
                ]);
            }
        } else {
            // 新しい休憩時間を作成
            $newBreakTime = BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $breakStart,
                'break_end' => $breakEnd
            ]);
            
            // 対応する修正リクエストを更新
            BreakTimeModification::where('attendance_mod_request_id', $attendanceModRequest->id)
                ->where('temp_index', $index)
                ->update(['break_times_id' => $newBreakTime->id]);
        }
    }
}

    /* public function update(AttendanceModificationRequest $request)
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
    } */
}
