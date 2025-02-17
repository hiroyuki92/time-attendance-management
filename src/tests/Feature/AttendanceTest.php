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

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
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

}
