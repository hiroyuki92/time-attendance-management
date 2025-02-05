<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'requested_clock_in' => 'required|date_format:H:i',
            'requested_clock_out' => 'required|date_format:H:i|after:requested_clock_in',
            'break_times.*.requested_break_start' => 'date_format:H:i|after_or_equal:requested_clock_in|before_or_equal:requested_clock_out',
            'break_times.*.requested_break_end' => 'date_format:H:i|after_or_equal:requested_break_start|before_or_equal:requested_clock_out|after_or_equal:requested_clock_in',
            'reason' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'requested_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です。',
            'requested_clock_in.required' => '出勤時間を入力してください。',
            'requested_clock_in.date_format' => '出勤時間は HH:mm 形式で入力してください。',
            'requested_clock_out.required' => '退勤時間を入力してください。',
            'requested_clock_out.date_format' => '出勤時間は HH:mm 形式で入力してください。',

            'break_times.*.requested_break_start.after_or_equal' => '休憩時間が勤務時間外です',
            'break_times.*.requested_break_start.before_or_equal' => '休憩時間が勤務時間外です',
            'break_times.*.requested_break_start.date_format' => '休憩終了時間は HH:mm 形式で入力してください。',
            'break_times.*.requested_break_end.after_or_equal' => '休憩終了時間は休憩開始時間以降に設定してください。',
            'break_times.*.requested_break_end.before_or_equal' => '休憩時間が勤務時間外です。',
            'break_times.*.requested_break_end.date_format' => '休憩終了時間は HH:mm 形式で入力してください。',

            'reason.required' => '備考を記入してください。',
        ];
    }
}
