<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class LeavingWorkTest extends TestCase
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
     * 退勤処理のテスト
     * 退勤ボタンが正しく機能するかテスト
     */
    public function leaving_work_button_works_correctly()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => null,
            'status' => 'working',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/attendance');
        $html = $response->getContent();
        $crawler = new Crawler($html);

        $this->assertEquals('退勤', $crawler->filter('.attendance-buttons .attendance-btn')->text());
        $response->assertViewHas('latestAttendanceStatus', 'working');

        $clockOutResponse = $this->post('/attendance/end');

        $afterClockOutResponse = $this->get('/attendance');
        $afterHtml = $afterClockOutResponse->getContent();
        $afterCrawler = new Crawler($afterHtml);

        $this->assertStringContainsString('退勤', $afterCrawler->filter('.status-badge')->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 退勤時刻が管理画面で確認できることの確認
     */
    public function admin_can_see_clock_out_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $response = $this->actingAs($this->user)
            ->get('/attendance');
        $clockInResponse = $this->post('/attendance/start');
        $clockInResponse->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2025, 2, 14, 18, 00));
        $clockOutResponse = $this->post('/attendance/end');
        $clockOutResponse->assertStatus(302);

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/attendance/list');
        $adminResponse->assertStatus(200);

        $adminHtml = $adminResponse->getContent();
        $crawler = new Crawler($adminHtml);

        $this->assertStringContainsString('18:00', $crawler->filter('td:nth-child(3)')->text());

        Carbon::setTestNow();

    }
}
