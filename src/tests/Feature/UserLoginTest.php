<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Fortify\Rules\Password;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);
    }

    /**
     * @test
     * ログイン認証機能（一般ユーザー）テスト
     * メールアドレスが未入力の場合、バリデーションメッセージが表示されるかテスト
     */
    public function shows_validation_message_when_email_is_empty()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => trans('validation.required', ['attribute' => 'メールアドレス'])
        ]);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示されるかテスト
     */
    public function shows_validation_message_when_password_is_empty()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => ''
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionHasErrors([
            'password' => trans('validation.required', ['attribute' => 'パスワード'])
        ]);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションメッセージが表示されるかテスト
     */
    public function shows_validation_message_when_credentials_do_not_match()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors([
            'email' => trans('auth.failed')
        ]);
    }
}
