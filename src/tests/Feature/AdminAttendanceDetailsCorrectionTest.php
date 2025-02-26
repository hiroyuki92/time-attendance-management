<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceModification;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class AdminAttendanceDetailsCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     * 管理者の勤怠詳細情報取得・修正機能テスト
     * 勤怠詳細画面に表示されるデータが選択したものになっているかテスト
     */
    public function displayed_admin_attendance_details()
    {
        $this->seed(\Database\Seeders\AttendanceSeeder::class);
        $this->seed(\Database\Seeders\BreakTimeSeeder::class);

        $attendance = Attendance::where('user_id', $this->user->id)->first();

        $this->actingAs($this->admin, 'admin');
        $adminResponse = $this->get("/admin/attendance/{$attendance->id}");
        $adminResponse->assertStatus(200);

        $html = $adminResponse->getContent();
        $crawler = new Crawler($html);

        $this->assertEquals($this->user->name, $crawler->filter('.user-name')->text());
        $this->assertEquals($attendance->clock_in->format('H:i'),
            $crawler->filter('input[name="requested_clock_in"]')->attr('value'));
        $this->assertEquals($attendance->clock_out->format('H:i'),
            $crawler->filter('input[name="requested_clock_out"]')->attr('value'));
    }

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されるかテスト
     */
    public function displayed_admin_error_message_when_clock_in_time_is_after_clock_out_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $this->actingAs($this->admin, 'admin');
        $adminResponse = $this->get("/admin/attendance/{$attendance->id}");
        $adminResponse->assertStatus(200);

        $updateData = [
            'attendance_id' => $attendance->id,
            'requested_work_date' => now()->toDateString(),
            'requested_clock_in' => '19:00',
            'requested_clock_out' => '18:00',
            'reason' => 'テスト',
            'status' => 'pending',
        ];
        $response = $this->actingAs($this->admin, 'admin')
        ->put("/admin/attendance/{$attendance->id}", $updateData);

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
    public function displayed_admin_error_message_when_break_start_time_is_after_clock_out_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $this->actingAs($this->admin, 'admin');
        $adminResponse = $this->get("/admin/attendance/{$attendance->id}");
        $adminResponse->assertStatus(200);

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
        $response = $this->actingAs($this->admin, 'admin')
        ->put("/admin/attendance/{$attendance->id}", $updateData);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'break_times.0.requested_break_start' => '休憩時間が勤務時間外です。'
        ]);

        Carbon::setTestNow();
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示されるかテスト
     */
    public function displayed_admin_error_message_when_break_end_time_is_after_clock_out_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $this->actingAs($this->admin, 'admin');
        $adminResponse = $this->get("/admin/attendance/{$attendance->id}");
        $adminResponse->assertStatus(200);

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
        $response = $this->actingAs($this->admin, 'admin')
        ->put("/admin/attendance/{$attendance->id}", $updateData);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'break_times.0.requested_break_end' => '休憩時間が勤務時間外です。'
        ]);

        Carbon::setTestNow();
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示されるかテスト
     */
    public function displayed_admin_error_message_when_reason_is_empty()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $this->actingAs($this->admin, 'admin');
        $adminResponse = $this->get("/admin/attendance/{$attendance->id}");
        $adminResponse->assertStatus(200);

        $updateData = [
            'attendance_id' => $attendance->id,
            'requested_year' => now()->format('Y') . '年',
            'requested_date' => now()->format('n月j日'),
            'requested_work_date' => now()->toDateString(),
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'reason' => '',
            'status' => 'pending',
        ];
        $response = $this->actingAs($this->admin, 'admin')
        ->put("/admin/attendance/{$attendance->id}", $updateData);

        $response->assertStatus(302)
        ->assertSessionHasErrors([
            'reason' => '備考を記入してください。'
        ]);

        Carbon::setTestNow();
    }

}
