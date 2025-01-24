<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'status'
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'work_date' => 'date',
    ];

    public function getTotalWorkTimeAttribute()
    {
        // 出勤時間と退勤時間があるか確認
        if (!$this->clock_in || !$this->clock_out) {
            return '00:00'; // 未設定の場合
        }

        // 出勤時間と退勤時間を Carbon インスタンスに変換
        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        // 勤務時間の差を取得
        $workDuration = $clockOut->diffInMinutes($clockIn);

        // 休憩時間を分で取得
        $breakDuration = $this->break_time ? $this->calculateBreakTime() : 0;

        // 合計勤務時間を計算 (分単位)
        $totalWorkTimeInMinutes = $workDuration - $breakDuration;

        // 時間と分の形式で返す
        $hours = floor($totalWorkTimeInMinutes / 60);
        $minutes = $totalWorkTimeInMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    protected function calculateBreakTime()
    {
        // 休憩時間が保存されている場合
        if ($this->break_time) {
            $breakDuration = Carbon::parse($this->break_end)->diffInMinutes(Carbon::parse($this->break_start));
            return $breakDuration;
        }

        return 0; // 休憩時間がない場合
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function break_times()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function attendance_mod_requests()
    {
        return $this->hasMany(AttendanceModRequest::class);
    }

}
