<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->seed(\Database\Seeders\AttendanceSeeder::class);
        $this->seed(\Database\Seeders\BreakTimeSeeder::class);
    }

    /**
     * @test
     * ユーザーの勤怠詳細情報取得機能テスト
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっているかテスト
     */
    public function displayed_user_name()
    {
        $attendance = Attendance::where('user_id', $this->user->id)->first();

        $response = $this->actingAs($this->user)
            ->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $this->assertEquals($this->user->name, $crawler->filter('.user-name')->text());
    }

    /**
     * @test
     * 勤怠詳細画面の「日付」が選択した日付になっているかテスト
     */
    public function displayed_work_date()
    {
        $attendance = Attendance::where('user_id', $this->user->id)->first();

        $response = $this->actingAs($this->user)
            ->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $this->assertEquals(\Carbon\Carbon::parse($attendance->work_date)->format('n月j日'),
            $crawler->filter('input[name="requested_date"]')->attr('value'));
    }

    /**
     * @test
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致しているかテスト
     */
    public function displayed_clock_in_and_clock_out_time()
    {
        $attendance = Attendance::where('user_id', $this->user->id)->first();

        $response = $this->actingAs($this->user)
            ->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $this->assertEquals($attendance->clock_in->format('H:i'),
            $crawler->filter('input[name="requested_clock_in"]')->attr('value'));
        $this->assertEquals($attendance->clock_out->format('H:i'),
            $crawler->filter('input[name="requested_clock_out"]')->attr('value'));
    }

    /**
     * @test
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致しているかテスト
     */
    public function displayed_break_time()
    {
        $attendance = Attendance::where('user_id', $this->user->id)->first();
        $breakTime = BreakTime::where('attendance_id', $attendance->id)->first();

        $response = $this->actingAs($this->user)
            ->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $this->assertEquals($breakTime->break_start->format('H:i'),
            $crawler->filter('input[name="break_times[0][requested_break_start]"]')->attr('value'));
        $this->assertEquals($breakTime->break_end->format('H:i'),
            $crawler->filter('input[name="break_times[0][requested_break_end]"]')->attr('value'));
    }
}
