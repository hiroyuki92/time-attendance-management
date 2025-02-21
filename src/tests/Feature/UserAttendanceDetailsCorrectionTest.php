<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class UserAttendanceDetailsCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     * ユーザーの勤怠詳細情報修正機能テスト
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されるかテスト
     */
    public function displayed_error_message_when_clock_in_time_is_after_clock_out_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $updateData = [
            'attendance_id' => $attendance->id,
            'requested_work_date' => now()->toDateString(),
            'requested_clock_in' => '19:00',
            'requested_clock_out' => '18:00',
            'reason' => 'テスト',
            'status' => 'pending',
        ];
        $response = $this->actingAs($this->user)
        ->post('/attendance/mod-request', $updateData);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'requested_clock_out' => '出勤時間もしくは退勤時間が不適切な値です。'
        ]);

        Carbon::setTestNow();
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示されるかテスト
     */
    public function displayed_error_message_when_break_start_time_is_after_clock_out_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $breakTime = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::create(2025, 2, 14, 12, 0),
            'break_end' => Carbon::create(2025, 2, 14, 13, 0),
        ]);

        $updateData = [
            'attendance_id' => $attendance->id,
            'requested_year' => now()->format('Y') . '年',
            'requested_date' => now()->format('n月j日'),
            'requested_work_date' => now()->toDateString(),
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'break_times' => [
                ['requested_break_start' => '21:00', 'requested_break_end' => '22:00']
            ],
            'reason' => 'テスト',
            'status' => 'pending',
        ];
        $response = $this->actingAs($this->user)
        ->post('/attendance/mod-request', $updateData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'break_times.0.requested_break_start' => '休憩時間が勤務時間外です。'
        ]);

        Carbon::setTestNow();
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示されるかテスト
     */
    public function displayed_error_message_when_break_end_time_is_after_clock_out_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $breakTime = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::create(2025, 2, 14, 12, 0),
            'break_end' => Carbon::create(2025, 2, 14, 13, 0),
        ]);

        $updateData = [
            'attendance_id' => $attendance->id,
            'requested_year' => now()->format('Y') . '年',
            'requested_date' => now()->format('n月j日'),
            'requested_work_date' => now()->toDateString(),
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'break_times' => [
                ['requested_break_start' => '12:00', 'requested_break_end' => '19:00']
            ],
            'reason' => 'テスト',
            'status' => 'pending',
        ];
        $response = $this->actingAs($this->user)
        ->post('/attendance/mod-request', $updateData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'break_times.0.requested_break_end' => '休憩時間が勤務時間外です。'
        ]);

        Carbon::setTestNow();
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示されるかテスト
     */
    public function displayed_error_message_when_reason_is_empty()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $updateData = [
            'attendance_id' => $attendance->id,
            'requested_work_date' => now()->toDateString(),
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'reason' => '',
            'status' => 'pending',
        ];
        $response = $this->actingAs($this->user)
        ->post('/attendance/mod-request', $updateData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください。'
        ]);

        Carbon::setTestNow();
    }

}
