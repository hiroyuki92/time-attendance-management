<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\Attendance;

class AttendanceModificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */


    public function rules()
    {
        $requestedWorkDate = null;
        try {
        $requestedWorkDate = Carbon::createFromFormat(
            'Y年m月d日',
            $this->requested_year . $this->requested_date
        )->format('Y-m-d');
        
        // コントローラーで使うために変換した日付をリクエストに追加
        $this->merge(['requested_work_date' => $requestedWorkDate]);
    } catch (\Exception $e) {
    }

        $targetUserId = $this->user_id ?? null;
        
        // 編集対象の勤怠データからユーザーIDを取得
        if (empty($targetUserId) && !empty($this->attendance_id)) {
            $attendance = Attendance::find($this->attendance_id);
            if ($attendance) {
                $targetUserId = $attendance->user_id;
            }
        }

        return [
            'requested_year' => 'required|regex:/^\d{4}年$/',
            'requested_date' => ['required',
                function ($attribute, $value, $fail) use ($requestedWorkDate, $targetUserId) {
                    if ($requestedWorkDate) {
                        $query = Attendance::where('user_id', $targetUserId)
                            ->where('work_date', $requestedWorkDate);
                        
                        if (!empty($this->attendance_id)) {
                            $query->where('id', '!=', (int)$this->attendance_id);
                        }
                        
                        if ($query->exists()) {
                            $fail('この日付の勤怠記録は既に存在します。');
                        }
                    }
                },
            ],
            'requested_clock_in' => 'required|date_format:H:i',
            'requested_clock_out' => 'required|date_format:H:i|after:requested_clock_in',
            'break_times.*.requested_break_start' => 'nullable|date_format:H:i|after_or_equal:requested_clock_in|before_or_equal:requested_clock_out',
            'break_times.*.requested_break_end' => 'nullable|date_format:H:i|after_or_equal:requested_break_start|before_or_equal:requested_clock_out|after_or_equal:requested_clock_in',
            'reason' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'requested_year.required' => '年を入力してください。',
            'requested_year.regex' => '年は「YYYY年」の形式で入力してください。（例:2025年）',
            'requested_date.required' => '日付を入力してください。',
            'requested_date.unique' => 'この日付の勤怠記録は既に存在します。',
            'requested_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です。',
            'requested_clock_in.required' => '出勤時間を入力してください。',
            'requested_clock_in.date_format' => '出勤時間は HH:mm 形式で入力してください。',
            'requested_clock_out.required' => '退勤時間を入力してください。',
            'requested_clock_out.date_format' => '退勤時間は HH:mm 形式で入力してください。',

            'break_times.*.requested_break_start.after_or_equal' => '休憩時間が勤務時間外です。',
            'break_times.*.requested_break_start.before_or_equal' => '休憩時間が勤務時間外です。',
            'break_times.*.requested_break_start.date_format' => '休憩終了時間は HH:mm 形式で入力してください。',
            'break_times.*.requested_break_end.after_or_equal' => '休憩終了時間は休憩開始時間以降に設定してください。',
            'break_times.*.requested_break_end.before_or_equal' => '休憩時間が勤務時間外です。',
            'break_times.*.requested_break_end.date_format' => '休憩終了時間は HH:mm 形式で入力してください。',

            'reason.required' => '備考を記入してください。',
            'reason.string' => '備考を文字列で入力してください。',
            'reason.max' => '備考を255文字以下で入力してください。',
        ];
    }
}
