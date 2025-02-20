<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class BreakTest extends TestCase
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
     * 休憩機能テスト
     * 休憩ボタンが正しく機能するかテスト
     */
    public function break_button_works_correctly()
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

        $this->assertEquals('休憩入', $crawler->filter('.attendance-buttons .break-btn')->text());
        $response->assertViewHas('latestAttendanceStatus', 'working');

        $breakStartResponse = $this->post('/attendance/break/start');
        $afterBreakStartResponse = $this->get('/attendance');
        $afterHtml = $afterBreakStartResponse->getContent();
        $afterCrawler = new Crawler($afterHtml);

        $this->assertStringContainsString('休憩中', $afterCrawler->filter('.status-badge')->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 休憩機能テスト
     * 休憩は一日に何回でもできるかテスト
     */
    public function user_can_take_break_multiple_times()
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
        
        $breakStartResponse = $this->post('/attendance/break/start');
        $breakEndResponse = $this->post('/attendance/break/end');
        $afterBreakEndResponse = $this->get('/attendance');

        $afterHtml = $afterBreakEndResponse->getContent();
        $afterCrawler = new Crawler($afterHtml);

        $this->assertEquals('休憩入', $afterCrawler->filter('.attendance-buttons .break-btn')->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 休憩機能テスト
     * 休憩戻ボタンが正しく機能するかテスト
     */
    public function break_end_button_works_correctly()
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
        $breakStartResponse = $this->post('/attendance/break/start');
        $afterBreakStartResponse = $this->get('/attendance');
        $afterHtml = $afterBreakStartResponse->getContent();
        $afterCrawler = new Crawler($afterHtml);
        $this->assertEquals('休憩戻', $afterCrawler->filter('.attendance-buttons .break-return-btn')->text());

        $breakEndResponse = $this->post('/attendance/break/end');
        $afterBreakEndResponse = $this->get('/attendance');
        $afterHtml = $afterBreakEndResponse->getContent();
        $afterCrawler = new Crawler($afterHtml);
        $this->assertStringContainsString('出勤中', $afterCrawler->filter('.status-badge')->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 休憩機能テスト
     * 休憩戻は一日に何回でもできるかテスト
     */
    public function user_can_take_break_end_multiple_times()
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
        
        $breakStartResponse = $this->post('/attendance/break/start');
        $breakEndResponse = $this->post('/attendance/break/end');
        $secondBreakStartResponse = $this->post('/attendance/break/start');

        $this->assertEquals(302, $secondBreakStartResponse->status());
        $afterSecondBreakStartResponse = $this->get('/attendance');

        $afterHtml = $afterSecondBreakStartResponse->getContent();
        $afterCrawler = new Crawler($afterHtml);

        $this->assertEquals('休憩戻', $afterCrawler->filter('.attendance-buttons .break-return-btn')->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 休憩機能テスト
     * 休憩時刻が管理画面で確認できる
     */
    public function admin_can_see_break_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $attendance = $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => null,
            'status' => 'working',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/attendance');
        Carbon::setTestNow(Carbon::create(2025, 2, 14, 10, 0));
        $breakStartResponse = $this->post('/attendance/break/start');
        $breakStartResponse->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2025, 2, 14, 10, 30));
        $breakEndResponse = $this->post('/attendance/break/end');
        $breakEndResponse->assertStatus(302);

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/attendance/list');
        $adminResponse->assertStatus(200);

        $adminDetailResponse = $this->get('/admin/attendance/' . $attendance->id);
        $adminDetailResponse->assertStatus(200);
        $adminHtml = $adminDetailResponse->getContent();

        $crawler = new Crawler($adminHtml);

        $this->assertEquals('10:00', $crawler->filter('input[name="break_times[0][requested_break_start]"]')->attr('value'));

        $this->assertEquals('10:30', $crawler->filter('input[name="break_times[0][requested_break_end]"]')->attr('value'));


        Carbon::setTestNow();
    }
}
