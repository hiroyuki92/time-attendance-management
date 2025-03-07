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

class AdminApprovalTest extends TestCase
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
     * 管理者の勤怠情報修正機能テスト
     * 承認待ちの修正申請が全て表示されているかテスト
     */
    public function displayed_admin_approval_list()
    {
        $specificDate = Carbon::now()->format('Y-m-d');
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $attendance = $user->attendances()->create([
                'work_date' => now()->toDateString(),
                'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
                'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
                'status' => 'left'
            ]);

            $attendanceModification = AttendanceModification::create([
                'attendance_id' => $attendance->id,
                'requested_year' => now()->subDay()->format('Y') . '年',
                'requested_date' => now()->subDay()->format('n月j日'),
                'requested_work_date' => now()->subDay()->toDateString(),
                'requested_clock_in' => '10:00',
                'requested_clock_out' => '20:00',
                'reason' => '遅刻',
                'status' => 'pending',
            ]);}

        $response = $this->actingAs($this->admin, 'admin')->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $approvalRows = $crawler->filter('tbody tr');
        $pendingRequests = AttendanceModification::where('status', 'pending')->get();

        $this->assertEquals(
            $pendingRequests->count(),
            $approvalRows->count(),
            '画面上の承認待ちの修正申請件数が正しくありません'
        );

        Carbon::setTestNow();
    }

    /**
     * @test
     * 承認済みの修正申請が全て表示されているかテスト
     */
    public function displayed_admin_approval_list_approved()
    {
        $specificDate = Carbon::now()->format('Y-m-d');
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $attendance = $user->attendances()->create([
                'work_date' => now()->toDateString(),
                'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
                'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
                'status' => 'left'
            ]);

            $attendanceModification = AttendanceModification::create([
                'attendance_id' => $attendance->id,
                'requested_year' => now()->subDay()->format('Y') . '年',
                'requested_date' => now()->subDay()->format('n月j日'),
                'requested_work_date' => now()->subDay()->toDateString(),
                'requested_clock_in' => '10:00',
                'requested_clock_out' => '20:00',
                'reason' => '遅刻',
                'status' => 'approved',
            ]);
        }

        $response = $this->actingAs($this->admin, 'admin')->get('/admin/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $approvalRows = $crawler->filter('tbody tr');
        $approvedRequests = AttendanceModification::where('status', 'approved')->get();

        $this->assertEquals(
            $approvedRequests->count(),
            $approvalRows->count(),
            '画面上の承認済みの修正申請件数が正しくありません'
        );

        Carbon::setTestNow();
    }

    /**
     * @test
     * 修正申請の詳細内容が正しく表示されているかテスト
     */
    public function displayed_admin_approval_detail()
    {
        $specificDate = Carbon::now()->format('Y-m-d');
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $attendance = $user->attendances()->create([
                'work_date' => now()->toDateString(),
                'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
                'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
                'status' => 'left'
            ]);

            $attendanceModification = AttendanceModification::create([
                'attendance_id' => $attendance->id,
                'requested_year' => now()->subDay()->format('Y') . '年',
                'requested_date' => now()->subDay()->format('n月j日'),
                'requested_work_date' => now()->subDay()->toDateString(),
                'requested_clock_in' => '10:00',
                'requested_clock_out' => '20:00',
                'reason' => '遅刻',
                'status' => 'pending',
            ]);
        }

        $pendingRequests = AttendanceModification::where('status', 'pending')->get();

        $response = $this->actingAs($this->admin, 'admin')->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $detailButtonUrl = $crawler->filter('td a.detail-link')->first()->attr('href');

        // **対応するユーザーを取得**
        $firstRequest = $pendingRequests->first();
        $user = $firstRequest->attendance->user;

        $detailResponse = $this->get($detailButtonUrl);
        $detailResponse->assertStatus(200);

        $detailHtml = $detailResponse->getContent();
        $detailCrawler = new Crawler($detailHtml);

        $this->assertStringContainsString($user->name, $detailCrawler->filter('.time-range__request')->text());
        $this->assertStringContainsString('遅刻', $detailCrawler->filter('.form-group_content-detail')->last()->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 修正申請の承認処理が正しく行われるかテスト
     */
    public function approved_admin_approval()
    {
        $specificDate = Carbon::now()->format('Y-m-d');
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $attendance = $user->attendances()->create([
                'work_date' => now()->toDateString(),
                'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
                'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
                'status' => 'left'
            ]);

        $attendanceModification = AttendanceModification::create([
            'attendance_id' => $attendance->id,
            'requested_year' => now()->format('Y') . '年',
            'requested_date' => now()->format('n月j日'),'requested_work_date' => now()->toDateString(),
            'requested_clock_in' => '10:00',
            'requested_clock_out' => '20:00',
            'reason' => '遅刻',
            'status' => 'pending',
        ]);
    }

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/stamp_correction_request/list');
        $adminResponse->assertStatus(200);

        $html = $adminResponse->getContent();
        $crawler = new Crawler($html);

        $detailButtonUrl = $crawler->filter('td a.detail-link')->first()->attr('href');
        
        $pendingRequests = AttendanceModification::where('status', 'pending')->get();
        $firstRequest = $pendingRequests->first();
        $user = $firstRequest->attendance->user;
        $attendanceId = $firstRequest->attendance_id;

        $detailResponse = $this->get($detailButtonUrl);
        $detailResponse->assertStatus(200);

        $detailHtml = $detailResponse->getContent();
        $detailCrawler = new Crawler($detailHtml);

        $approvalForm = $detailCrawler->filter('form.button-container')->first();

        $approvalFormAction = $approvalForm->attr('action');

        $approveResponse = $this->post($approvalFormAction, [
            '_token' => csrf_token()
        ]);
        $approveResponse->assertStatus(302);

        $updatedRequest = AttendanceModification::where('attendance_id', $attendanceId)->first();
        $this->assertEquals('approved', $updatedRequest->status);

        $updatedAttendance = Attendance::where('id', $attendanceId)->first();
        $updatedAttendance->refresh();

        $this->assertEquals($updatedRequest->requested_clock_in, $updatedAttendance->clock_in);
        $this->assertEquals($updatedRequest->requested_clock_out, $updatedAttendance->clock_out);

        Carbon::setTestNow(null);
    }
}
