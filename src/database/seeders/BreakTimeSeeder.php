<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $attendances = Attendance::all();
        foreach ($attendances as $attendance) {
            $workDate = Carbon::parse($attendance->work_date);

            if($attendance->status !== 'left') {
                $breakStart = $workDate->copy()->setHour(12)->setMinute(0)->setSecond(0);
                $breakEnd = null;

                if (Carbon::now()->format('H') >= 13) {
                    $breakEnd = $workDate->copy()->setHour(13)->setMinute(0)->setSecond(0);
                }

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                ]);
            } else {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $workDate->copy()->setHour(12)->setMinute(0)->setSecond(0),
                    'break_end' => $workDate->copy()->setHour(13)->setMinute(0)->setSecond(0),
                ]);

                if (rand(1, 100) <= 30) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $workDate->copy()->setHour(10)->setMinute(30)->setSecond(0),
                        'break_end' => $workDate->copy()->setHour(10)->setMinute(45)->setSecond(0),
                    ]);
                }

                if (rand(1, 100) <= 30) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $workDate->copy()->setHour(15)->setMinute(0)->setSecond(0),
                        'break_end' => $workDate->copy()->setHour(15)->setMinute(15)->setSecond(0),
                    ]);
                }
            }
        }
    }
}
