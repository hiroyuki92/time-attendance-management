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

class UserAttendanceDetailsCorrectionTest extends TestCase
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
            'requested_year' => now()->format('Y') . '年',
            'requested_date' => now()->format('n月j日'),
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

    /**
     * @test
     * 修正申請処理が実行されるかテスト
     */
    public function execute_mod_request()
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
            'requested_year' => now()->format('Y') . '年',
            'requested_date' => now()->format('n月j日'),
            'requested_work_date' => now()->toDateString(),
            'requested_clock_in' => '10:00',
            'requested_clock_out' => '18:00',
            'reason' => '遅刻',
            'status' => 'pending',
        ];
        $response = $this->actingAs($this->user)
        ->post('/attendance/mod-request', $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_mod_requests', [
            'attendance_id' => $attendance->id,
            'requested_clock_in' => Carbon::create(2025, 2, 14, 10, 0),
            'requested_clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'reason' => '遅刻',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin,'admin');

        $adminResponse = $this->get('/admin/stamp_correction_request/list');
        $adminResponse->assertStatus(200);

        $adminHtml = $adminResponse->getContent();
        $crawler = new Crawler($adminHtml);


        $this->assertStringContainsString('遅刻', $crawler->filter('td:nth-child(4)')->text());

        $modRequest = AttendanceModification::where('attendance_id', $attendance->id)->first();
        $approveResponse = $this->get("/admin/stamp_correction_request/approve/{$modRequest->id}");
        $approveResponse->assertStatus(200);

        $approveHtml = $approveResponse->getContent();
        $approveCrawler = new Crawler($approveHtml);

        $this->assertStringContainsString('遅刻', $approveCrawler->filter('.form-group_content-detail')->eq(4)->text());
        $this->assertStringContainsString('10:00', $approveCrawler->filter('.form-group_content-detail')->eq(2)->text());
        $this->assertStringContainsString('18:00', $approveCrawler->filter('.form-group_content-detail')->eq(3)->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 「承認待ち」にログインユーザーが行った申請が全て表示されているかテスト
     */
    public function displayed_all_pending_mod_requests()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $anotherUser = User::factory()->create();

        $attendance1 = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        // ユーザー1の別日の勤務データを作成
        $attendance2 = $this->user->attendances()->create([
            'work_date' => now()->subDay()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 13, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 13, 18, 0),
            'status' => 'left',
        ]);

        // 別ユーザーの勤務データを作成
        $attendance3 = $anotherUser->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        // ユーザー1の1つ目の申請
        $this->actingAs($this->user)
            ->post('/attendance/mod-request', [
                'attendance_id' => $attendance1->id,
                'requested_year' => now()->format('Y') . '年',
                'requested_date' => now()->format('n月j日'),
                'requested_work_date' => now()->toDateString(),
                'requested_clock_in' => '10:00',
                'requested_clock_out' => '18:00',
                'reason' => '遅刻',
                'status' => 'pending',
            ]);

        // ユーザー1の2つ目の申請
        $this->actingAs($this->user)
            ->post('/attendance/mod-request', [
                'attendance_id' => $attendance2->id,
                'requested_year' => now()->subDay()->format('Y') . '年',
                'requested_date' => now()->subDay()->format('n月j日'),
                'requested_work_date' => now()->subDay()->toDateString(),
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '19:00',
                'reason' => '残業',
                'status' => 'pending',
            ]);

        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $reasonCells = $crawler->filter('tr td:nth-child(4)')->each(function ($node) {
            return $node->text();
        });

        $this->assertContains('遅刻', $reasonCells);
        $this->assertContains('残業', $reasonCells);

        $this->assertEquals(2, $crawler->filter('tr')->count() - 1);

        Carbon::setTestNow();
    }

    /**
     * @test
     * 「承認済み」に管理者が承認した修正申請が全て表示されているかテスト
     */
    public function displayed_all_approved_mod_requests()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance1 = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        // ユーザー1の別日の勤務データを作成
        $attendance2 = $this->user->attendances()->create([
            'work_date' => now()->subDay()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 13, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 13, 18, 0),
            'status' => 'left',
        ]);

        // ユーザー1の1つ目の申請
        $this->actingAs($this->user)
            ->post('/attendance/mod-request', [
                'attendance_id' => $attendance1->id,
                'requested_year' => now()->format('Y') . '年',
                'requested_date' => now()->format('n月j日'),
                'requested_work_date' => now()->toDateString(),
                'requested_clock_in' => '10:00',
                'requested_clock_out' => '18:00',
                'reason' => '遅刻',
                'status' => 'pending',
            ]);

        // ユーザー1の2つ目の申請
        $this->actingAs($this->user)
            ->post('/attendance/mod-request', [
                'attendance_id' => $attendance2->id,
                'requested_year' => now()->subDay()->format('Y') . '年',
                'requested_date' => now()->subDay()->format('n月j日'),
                'requested_work_date' => now()->subDay()->toDateString(),
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '19:00',
                'reason' => '残業',
                'status' => 'pending',
            ]);

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/stamp_correction_request/list');
        $adminResponse->assertStatus(200);

        // ユーザー1の1つ目の申請を承認
        $modRequest = AttendanceModification::where('attendance_id', $attendance1->id)->first();
        $approveResponse = $this->get("/admin/stamp_correction_request/approve/{$modRequest->id}");

        $approvePostResponse = $this->post("/admin/stamp_correction_request/approve/{$modRequest->id}");
        $approvePostResponse->assertStatus(302);

        $this->assertDatabaseHas('attendance_mod_requests', [
            'id' => $modRequest->id,
            'status' => 'approved',
        ]);

        // ユーザー1の2つ目の申請を承認
        $modRequest = AttendanceModification::where('attendance_id', $attendance2->id)->first();
        $approveResponse = $this->get("/admin/stamp_correction_request/approve/{$modRequest->id}");

        $approvePostResponse = $this->post("/admin/stamp_correction_request/approve/{$modRequest->id}");
        $approvePostResponse->assertStatus(302);

        $this->assertDatabaseHas('attendance_mod_requests', [
            'id' => $modRequest->id,
            'status' => 'approved',
        ]);


        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $reasonCells = $crawler->filter('tr td:nth-child(4)')->each(function ($node) {
            return $node->text();
        });

        $this->assertContains('遅刻', $reasonCells);
        $this->assertContains('残業', $reasonCells);

        $this->assertEquals(2, $crawler->filter('tr')->count() - 1);


        Carbon::setTestNow();

    }

    /**
     * @test
     * 各申請の「詳細」を押下すると申請詳細画面に遷移するかテスト
     */
    public function transition_to_mod_request_detail_page_when_click_detail_button()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left',
        ]);

        $this->actingAs($this->user)
            ->post('/attendance/mod-request', [
                'attendance_id' => $attendance->id,
                'requested_year' => now()->format('Y') . '年',
                'requested_date' => now()->format('n月j日'),
                'requested_work_date' => now()->toDateString(),
                'requested_clock_in' => '10:00',
                'requested_clock_out' => '18:00',
                'reason' => '遅刻',
                'status' => 'pending',
            ]);

        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $detailButtonUrl = $crawler->filter('td a.detail-link')->first()->attr('href');

        $detailResponse = $this->get($detailButtonUrl);
        $detailResponse->assertStatus(200);

        $detailHtml = $detailResponse->getContent();
        $detailCrawler = new Crawler($detailHtml);

        $this->assertStringContainsString('遅刻', $detailCrawler->filter('.form-text-content')->text());

        Carbon::setTestNow();
    }

}
