<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        $startDate = Carbon::now()->subMonths(3);
        $endDate = Carbon::now();

        $users = User::where('role', '!=', 'admin')->get();

        foreach ($users as $user) {
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                if ($currentDate->isWeekend()) {
                    $currentDate->addDay();
                    continue;
                }

                if (rand(1, 100) <= 95) {
                    $clockInTime = $currentDate->copy()->setHour(9)->setMinute(rand(0, 30));
                    $currentTime = Carbon::now();
                    $status = 'left';
                    $clockOutTime = null;

                    if ($currentDate->isToday()) {
                        if ($currentTime->format('H') < 18) {
                            $status = rand(1, 100) <= 80 ? 'working' : 'breaking';
                            $clockOutTime = null;
                        } else {
                            $status = 'left';
                            $clockOutTime = $currentDate->copy()->setHour(18)->setMinute(rand(0, 60));
                        }
                    } else {
                        $status = 'left';
                        $clockOutTime = $currentDate->copy()->setHour(18)->setMinute(rand(0, 60));
                    }

                    Attendance::create([
                        'user_id' => $user->id,
                        'work_date' => $currentDate->format('Y-m-d'),
                        'clock_in' => $clockInTime,
                        'clock_out' => $clockOutTime,
                        'status' => $status,
                    ]);
                }
                $currentDate->addDay();
            }
        }
    }
}
