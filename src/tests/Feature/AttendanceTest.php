<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
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
}
