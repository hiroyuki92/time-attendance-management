<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected static function getValidationMessage($requestClass, $validationKey)
    {
        $request = app($requestClass);
        $messages = $request->messages();

        return $messages[$validationKey] ?? null;

    }
    /**
     * @dataProvider validationMessageDataProvider
     */
    public function test_login_admin_validation_messages($field, $value, $expectedMessage)
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $data = [
            'email' => 'test_admin@example.com',
            'password' => 'test_password',
        ];
        $data[$field] = $value;


        $actualMessage = $expectedMessage === 'invalid_credentials'
        ? 'ログイン情報が登録されていません。'
        : $this->getValidationMessage(
            \App\Http\Requests\Admin\AdminLoginRequest::class,
            $expectedMessage
        );

        $response = $this->post('/admin/login', $data);

        if ($expectedMessage === 'ログイン情報が登録されていません。') {
            $response->assertSessionHasNoErrors();
            $response->assertSee($expectedMessage);
        } else {
            $response->assertSessionHasErrors([
                $field => $expectedMessage,
            ]);
        }
    }

    public static function validationMessageDataProvider()
    {
        return [
            'email_required' => [
                'email', '', self::getValidationMessage(\App\Http\Requests\Admin\AdminLoginRequest::class, 'email.required')
            ],
            'password_required' => [
                'password', '', self::getValidationMessage(\App\Http\Requests\Admin\AdminLoginRequest::class, 'password.required')
            ],
            'invalid_credentials' => [
                'email',
                'wrong@example.com',
                'ログイン情報が登録されていません。'
            ],
        ];
    }
}
