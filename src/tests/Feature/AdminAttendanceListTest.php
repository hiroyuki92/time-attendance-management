<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class AdminAttendanceListTest extends TestCase
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
     * 管理者の勤怠一覧情報取得機能テスト
     * その日になされた全ユーザーの勤怠情報が正確に確認できるかテスト
     */
    public function displayed_admin_attendance_list()
    {
        $specificDate = Carbon::now()->format('Y-m-d');
        $users = User::factory()->count(3)->create();

        foreach ($users as $user)
        {$attendance = $user->attendances()->create([
            'work_date' => now()->toDateString(),
            'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
            'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
            'status' => 'left']);}

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/attendance/list');
        $adminResponse->assertStatus(200);


        $html = $adminResponse->getContent();
        $crawler = new Crawler($html);

        $attendanceRows = $crawler->filter('tbody tr');
        $this->assertEquals($users->count(), $attendanceRows->count(), '画面上の勤怠件数が正しくありません');

        $attendanceRows->each(function ($node, $index) use ($users) {
        $user = $users[$index];

            $this->assertStringContainsString($user->name, $node->filter('td:nth-child(1)')->text());

            $this->assertStringContainsString('09:00', $node->filter('td:nth-child(2)')->text());
            $this->assertStringContainsString('18:00', $node->filter('td:nth-child(3)')->text());

        });

        Carbon::setTestNow();
    }

    /**
     * @test
     * 管理者の勤怠一覧情報取得機能テスト
     * 遷移した際に現在の日付が表示されるかテスト
     */
    public function displayed_current_date()
    {
        $currentDate = Carbon::now()->format('Y/m/d');

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/attendance/list');
        $html = $adminResponse->getContent();
        $crawler = new Crawler($html);

        $this->assertStringContainsString($currentDate, $crawler->filter('.current-month')->text());
    }

    /**
     * @test
     * 「前日」を押下した時に前の日の勤怠情報が表示されるかテスト
     */
    public function displayed_previous_date()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);
        $currentDate = Carbon::now()->format('Y/m/d');
        $previousDate = Carbon::now()->subDay()->format('Y/m/d');

        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $clockIn = Carbon::parse($previousDate)->setTime(9, 0)->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s');
            $clockOut = Carbon::parse($previousDate)->setTime(18, 0)->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s');

            $attendance = $user->attendances()->create([
                'work_date' => $previousDate,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => 'left'
            ]);
        }

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/attendance/list');
        $adminResponse->assertStatus(200);

        $html = $adminResponse->getContent();
        $crawler = new Crawler($html);

        $previousButtonUrl = $crawler->filter('.btn-primary')->first()->attr('href');
        $previousDayResponse = $this->get($previousButtonUrl);
        $previousDayResponse->assertStatus(200);

        $previousDayHtml = $previousDayResponse->getContent();
        $previousDayCrawler = new Crawler($previousDayHtml);

        $this->assertStringContainsString($previousDate, $previousDayCrawler->filter('.current-month')->text());

        $attendanceRows = $previousDayCrawler->filter('tbody tr');
        $this->assertEquals($users->count(), $attendanceRows->count(), '画面上の勤怠件数が正しくありません');

        $attendanceRows->each(function ($node, $index) use ($users) {
            $user = $users[$index];

            $this->assertStringContainsString($user->name, $node->filter('td:nth-child(1)')->text());

            $this->assertStringContainsString('09:00', $node->filter('td:nth-child(2)')->text());
            $this->assertStringContainsString('18:00', $node->filter('td:nth-child(3)')->text());
        });

        Carbon::setTestNow(null);
    }

    /**
     * @test
     * 「翌日」を押下した時に次の日の勤怠情報が表示されるかテスト
     */
    public function displayed_next_date()
    {
        $now = Carbon::create(2025, 2, 14, 9, 0);
        Carbon::setTestNow($now);
        $currentDate = Carbon::now()->format('Y/m/d');
        $nextDate = Carbon::now()->addDay()->format('Y/m/d');

        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $clockIn = Carbon::parse($nextDate)->setTime(9, 0)->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s');
            $clockOut = Carbon::parse($nextDate)->setTime(18, 0)->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s');

            $attendance = $user->attendances()->create([
                'work_date' => $nextDate,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => 'left'
            ]);
        }

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/attendance/list');
        $adminResponse->assertStatus(200);

        $html = $adminResponse->getContent();
        $crawler = new Crawler($html);

        $nextButtonUrl = $crawler->filter('.btn-primary')->eq(1)->attr('href');
        $nextDayResponse = $this->get($nextButtonUrl);
        $nextDayResponse->assertStatus(200);

        $nextDayHtml = $nextDayResponse->getContent();
        $nextDayCrawler = new Crawler($nextDayHtml);

        $this->assertStringContainsString($nextDate, $nextDayCrawler->filter('.current-month')->text());

        $attendanceRows = $nextDayCrawler->filter('tbody tr');
        $this->assertEquals($users->count(), $attendanceRows->count(), '画面上の勤怠件数が正しくありません');

        $attendanceRows->each(function ($node, $index) use ($users) {
            $user = $users[$index];

            $this->assertStringContainsString($user->name, $node->filter('td:nth-child(1)')->text());

            $this->assertStringContainsString('09:00', $node->filter('td:nth-child(2)')->text());
            $this->assertStringContainsString('18:00', $node->filter('td:nth-child(3)')->text());
        });

        Carbon::setTestNow(null);
    }
}
