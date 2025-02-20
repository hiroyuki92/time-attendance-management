<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelectorConverter;

class AttendanceTest extends TestCase
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
     * 勤怠打刻画面の表示テスト
     */

    public function displayed_datetime_matches_current_datetime()
    {
        // 現在時刻を固定
        $now = Carbon::create(2025, 2, 14, 13, 55);
        Carbon::setTestNow($now);
        $response = $this->actingAs($this->user)
            ->get('/attendance');
        
        $html = $response->getContent();
        $crawler = new Crawler($html);

        $displayedDate = $crawler->filter('.date')->text();
        $displayedTime = $crawler->filter('.time')->text();

        $expectedDate = $now->format('Y年n月j日');
        $expectedTime = $now->format('H:i');

        $this->assertEquals($expectedDate, $displayedDate);
        $this->assertEquals($expectedTime, $displayedTime);

        Carbon::setTestNow();
    }

    /**
     * @test
     * @dataProvider statusBadgeProvider
     * ステータス確認機能のテスト
     */
    public function testStatusBadgeDisplay(string $status, string $expectedText)
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => now()->format('Y-m-d'),
            'status' => $status,
            'clock_in' => $status === 'working' || $status === 'breaking' ? now() : null,
            'clock_out' => $status === 'left' ? now() : null
        ]);
        $response = $this->actingAs($this->user)
            ->get('/attendance')
            ->assertStatus(200);

        $response->assertSee($expectedText);
    }

    public function statusBadgeProvider(): array
    {
        return [
            '勤務外の場合' => [
                'status' => 'no_record',
                'expectedText' => '勤務外'
            ],
            '勤務中の場合' => [
                'status' => 'working',
                'expectedText' => '出勤中'
            ],
            '休憩中の場合' => [
                'status' => 'breaking',
                'expectedText' => '休憩中'
            ],
            '退勤済の場合' => [
                'status' => 'left',
                'expectedText' => '退勤済'
            ]
        ];
    }
    /**
     * @test
     * 出勤処理のテスト
     * 出勤ボタンが正しく機能することを確認
     */
    public function user_can_clock_in_when_no_active_attendance()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);
        $response = $this->actingAs($this->user)
            ->get('/attendance');

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $this->assertTrue($crawler->filter('.attendance-buttons .attendance-btn')->count() > 0);
        $this->assertEquals('出勤', $crawler->filter('.attendance-buttons .attendance-btn')->text());
        $response->assertViewHas('latestAttendanceStatus', 'no_record');

        $clockInResponse = $this->post('/attendance/start');

        $afterClockInResponse = $this->get('/attendance');
        $afterHtml = $afterClockInResponse->getContent();
        $afterCrawler = new Crawler($afterHtml);

        $this->assertStringContainsString('出勤中', $afterCrawler->filter('.status-badge')->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 出勤は一日一回のみできることの確認
     */
    public function user_cannot_see_clock_in_button_after_clock_out()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $this->user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => $now,
            'status' => 'left',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/attendance');

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $this->assertEquals('0', $crawler->filter('.attendance-buttons .attendance-btn')->count());

        Carbon::setTestNow();
    }
    /**
     * @test
     * 出勤時刻が管理画面で確認できることの確認
     */
    public function admin_can_see_clock_in_time()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);

        $this->actingAs($this->user);

        $response = $this->get('/attendance');

        $clockInResponse = $this->post('/attendance/start');
        $clockInResponse->assertStatus(302);

        $this->actingAs($this->admin,'admin');

        $adminResponse = $this->get('/admin/attendance/list');
        $adminResponse->assertStatus(200);

        $adminHtml = $adminResponse->getContent();
        $crawler = new Crawler($adminHtml);

        $this->assertStringContainsString('09:00', $crawler->filter('td:nth-child(2)')->text());

        Carbon::setTestNow();
    }

}
