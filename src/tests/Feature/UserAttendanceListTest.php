<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->seed(\Database\Seeders\AttendanceSeeder::class);
    }

    /**
     * @test
     * ユーザーの勤怠一覧情報取得機能テスト
     * 自分が行った勤怠情報が全て表示されているかテスト
     */
    public function displayed_user_attendance_list()
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $attendanceRecords = Attendance::where
        ('user_id', $this->user->id)
        ->whereBetween('work_date', [$currentMonthStart, $currentMonthEnd])
        ->get();

        $response = $this->actingAs($this->user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $attendanceRows = $crawler->filter('tbody tr');
        $this->assertEquals($attendanceRecords->count(), $attendanceRows->count(), '画面上の勤怠件数が正しくありません');

        foreach ($attendanceRecords as $attendance) {
            $formattedDate = Carbon::parse($attendance->work_date)->isoFormat('M月D日(dd)');
            
            $this->assertStringContainsString(
                    $formattedDate,
                    $html,
                    "日付 {$formattedDate} が表示されていません"
                );
        }
    }

    /**
     * @test
     * 勤怠一覧画面に遷移した際に現在の月が表示されるかテスト
     */
    public function displayed_current_month()
    {
        $currentMonth = Carbon::now()->format('Y/m');

        $response = $this->actingAs($this->user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $this->assertStringContainsString(
            $currentMonth,
            $crawler->filter('.current-month')->text(),
            '現在の月が表示されていません'
        );
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示されるかテスト
     */
    public function displayed_previous_month_link()
    {
        $baseDate = Carbon::create(2025, 2, 1);
        Carbon::setTestNow($baseDate);

        $previousMonthStart = $baseDate->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $baseDate->copy()->subMonth()->endOfMonth();

        $previousAttendanceRecords = Attendance::where
        ('user_id', $this->user->id)
        ->whereBetween('work_date', [$previousMonthStart, $previousMonthEnd])
        ->get();

        $response = $this->actingAs($this->user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $previousMonthUrl = $crawler->filter('.month-nav-button a')->attr('href');

        $previousMonthResponse = $this->actingAs($this->user)->get($previousMonthUrl);
        $previousMonthResponse->assertStatus(200);
        $previousMonthHtml = $previousMonthResponse->getContent();
        $crawler = new Crawler($previousMonthHtml);

        $expectedPreviousMonth = $baseDate->copy()->subMonth()->format('Y/m');

        $this->assertStringContainsString(
            $expectedPreviousMonth,
            $crawler->filter('.current-month')->text(),
            '前月が表示されていません'
        );

        $previousAttendanceRows = $crawler->filter('tbody tr');
        $this->assertEquals($previousAttendanceRecords->count(), $previousAttendanceRows->count(), '画面上の勤怠件数が正しくありません');

        foreach ($previousAttendanceRecords as $attendance) {
            $formattedDate = Carbon::parse($attendance->work_date)->isoFormat('M月D日(dd)');
            
            $this->assertStringContainsString(
                    $formattedDate,
                    $previousMonthHtml,
                    "日付 {$formattedDate} が表示されていません"
                );
        }
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の前月の情報が表示されるかテスト
     */
    public function displayed_next_month()
    {
        $baseDate = Carbon::create(2025, 2, 1);
        Carbon::setTestNow($baseDate);

        $nextMonthStart = $baseDate->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $baseDate->copy()->addMonth()->endOfMonth();

        $nextAttendanceRecords = Attendance::where
        ('user_id', $this->user->id)
        ->whereBetween('work_date', [$nextMonthStart, $nextMonthEnd])
        ->get();

        $response = $this->actingAs($this->user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $nextMonthUrl = $crawler->filter('.month-nav-button')->eq(1)->children('a')->attr('href');

        $nextMonthResponse = $this->actingAs($this->user)->get($nextMonthUrl);
        $nextMonthResponse->assertStatus(200);
        $nextMonthHtml = $nextMonthResponse->getContent();
        $crawler = new Crawler($nextMonthHtml);

        $expectedNextMonth = $baseDate->copy()->addMonth()->format('Y/m');

        $this->assertStringContainsString(
            $expectedNextMonth,
            $crawler->filter('.current-month')->text(),
            '翌月が表示されていません'
        );

        $nextAttendanceRows = $crawler->filter('tbody tr');
        $this->assertEquals($nextAttendanceRecords->count(), $nextAttendanceRows->count(), '画面上の勤怠件数が正しくありません');

        foreach ($nextAttendanceRecords as $attendance) {
            $formattedDate = Carbon::parse($attendance->work_date)->isoFormat('M月D日(dd)');
            
            $this->assertStringContainsString(
                    $formattedDate,
                    $nextMonthHtml,
                    "日付 {$formattedDate} が表示されていません"
                );
        }
    }
    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移するかテスト
     */
    public function move_to_attendance_detail()
    {
        $attendance = $this->user->attendances()
        ->latest('work_date')
        ->firstOrFail();

        $response = $this->actingAs($this->user)
            ->get('/attendance/list');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $detailUrl = $crawler->filter('.detail-link')->last()->attr('href');

        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
        $detailHtml = $detailResponse->getContent();
        $detailCrawler = new Crawler($detailHtml);

        $this->assertEquals(
            \Carbon\Carbon::parse($attendance->work_date)->format('n月j日'),
            $detailCrawler->filter('input[name="requested_date"]')->attr('value'),
            '日付が正しく表示されていません'
        );
    }
}
