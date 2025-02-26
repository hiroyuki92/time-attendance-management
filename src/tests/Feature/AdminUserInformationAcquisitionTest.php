<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class AdminUserInformationAcquisitionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @test
     * 管理者のユーザー情報取得機能テスト
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できるかテスト
     */
    public function displayed_admin_user_information()
    {
        $users = User::factory()->count(3)->create();

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/staff/list');
        $adminResponse->assertStatus(200);

        $html = $adminResponse->getContent();
        $crawler = new Crawler($html);

        $userRows = $crawler->filter('tbody tr');
        $this->assertEquals($users->count(), $userRows->count(), '画面上のユーザー数が正しくありません');

        $userRows->each(function ($node, $index) use ($users) {
            $user = $users[$index];

            $this->assertStringContainsString($user->name, $node->filter('td:nth-child(1)')->text());
            $this->assertStringContainsString($user->email, $node->filter('td:nth-child(2)')->text());
        });
    }

    /**
     * @test
     * ユーザーの勤怠情報が正しく表示されるかテスト
     */
    public function displayed_user_attendance_information()
    {
        $users = User::factory()->count(3)->create();

        foreach ($users as $user)
        {
            $user->attendances()->create([
                'work_date' => now()->toDateString(),
                'clock_in' => Carbon::create(2025, 2, 14, 9, 0),
                'clock_out' => Carbon::create(2025, 2, 14, 18, 0),
                'status' => 'left',
            ]);
        }

        $user = $users->first();

        $this->actingAs($this->admin,'admin');
        $adminResponse = $this->get('/admin/staff/list');
        $adminResponse->assertStatus(200);

        $html = $adminResponse->getContent();
        $crawler = new Crawler($html);

        $attendanceDetailUrl = $crawler->filter('.detail-link')->attr('href');

        $attendanceResponse = $this->get($attendanceDetailUrl);
        $attendanceResponse->assertStatus(200);
        $attendanceDetailHtml = $attendanceResponse->getContent();
        $crawler = new Crawler($attendanceDetailHtml);

        $headingText = trim($crawler->filter('h1.heading-text')->text());
        $this->assertEquals("{$user->name} さんの勤怠", $headingText);

        $attendanceRow = $crawler->filter('tbody tr')->first();
        $this->assertStringContainsString('09:00', trim($attendanceRow->filter('td:nth-child(2)')->text()));
        $this->assertStringContainsString('18:00', trim($attendanceRow->filter('td:nth-child(3)')->text()));
    }

    /**
     * @test
     * 「前日」を押下した時に表示日の前日の情報が表示されるかテスト
     */
    public function displayed_admin_previous_day_link()
    {
        $user = User::factory()->create();

        $baseDate = Carbon::create(2025, 2, 1);
        Carbon::setTestNow($baseDate);

        $previousDay = $baseDate->copy()->subDay();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $previousDay->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => 'left',
        ]);

        $response = $this->actingAs($this->admin,'admin')
            ->get('/admin/attendance/list');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $previousDayUrl = $crawler->filter('.month-nav-button a')->attr('href');

        $previousDayResponse = $this->actingAs($this->admin)->get($previousDayUrl);
        $previousDayResponse->assertStatus(200);
        $previousDayHtml = $previousDayResponse->getContent();
        $crawler = new Crawler($previousDayHtml);

        $expectedPreviousDay = $previousDay->format('Y/m/d');
        $this->assertStringContainsString(
            $expectedPreviousDay,
            $crawler->filter('.current-month')->text(),
            '前日が表示されていません'
        );

        $this->assertStringContainsString('09:00', $crawler->filter('td:nth-child(2)')->text());
        $this->assertStringContainsString('18:00', $crawler->filter('td:nth-child(3)')->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 「翌日」を押下した時に表示日の翌日の情報が表示されるかテスト
     */
    public function displayed_admin_next_day_link()
    {
        $user = User::factory()->create();

        $baseDate = Carbon::create(2025, 2, 1);
        Carbon::setTestNow($baseDate);

        $nextDay = $baseDate->copy()->addDay();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $nextDay->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => 'left',
        ]);

        $response = $this->actingAs($this->admin,'admin')
            ->get('/admin/attendance/list');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $nextDayUrl = $crawler->filter('.month-nav-button')->eq(1)->children('a')->attr('href');

        $nextDayResponse = $this->actingAs($this->admin)->get($nextDayUrl);
        $nextDayResponse->assertStatus(200);
        $nextDayHtml = $nextDayResponse->getContent();
        $crawler = new Crawler($nextDayHtml);

        $expectedNextDay = $nextDay->format('Y/m/d');
        $this->assertStringContainsString(
            $expectedNextDay,
            $crawler->filter('.current-month')->text(),
            '翌日が表示されていません'
        );

        $this->assertStringContainsString('09:00', $crawler->filter('td:nth-child(2)')->text());
        $this->assertStringContainsString('18:00', $crawler->filter('td:nth-child(3)')->text());

        Carbon::setTestNow();
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移するかテスト
     */
    public function displayed_admin_attendance_detail_link()
    {
        $user = User::factory()->create();

        $baseDate = Carbon::create(2025, 2, 1);
        Carbon::setTestNow($baseDate);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => 'left',
        ]);

        $response = $this->actingAs($this->admin,'admin')
            ->get('/admin/attendance/list');
        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $attendanceDetailUrl = $crawler->filter('.detail-link')->attr('href');

        $attendanceDetailResponse = $this->actingAs($this->admin)->get($attendanceDetailUrl);
        $attendanceDetailResponse->assertStatus(200);
        $attendanceDetailHtml = $attendanceDetailResponse->getContent();
        $attendanceDetailCrawler = new Crawler($attendanceDetailHtml);

        $this->assertEquals(\Carbon\Carbon::parse($attendance->work_date)->format('n月j日'),
            $attendanceDetailCrawler->filter('input[name="requested_date"]')->attr('value'));
        $this->assertEquals('09:00', $attendanceDetailCrawler->filter('input[name="requested_clock_in"]')->attr('value'));
        $this->assertEquals('18:00', $attendanceDetailCrawler->filter('input[name="requested_clock_out"]')->attr('value'));

        Carbon::setTestNow();
    }
}
