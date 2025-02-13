<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    protected function getValidationMessage($requestClass, $validationKey)
    {
        $request = app($requestClass);
        $messages = $request->messages();

        return $messages[$validationKey] ?? null;

    }
    /**
     * @dataProvider validationMessageDataProvider
     */
    public function test_register_user_validation_messages($field, $value, $expectedMessage)
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザ',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            $field => $value,
        ]);
        $response->assertSessionHasErrors([
            $field => $expectedMessage,
        ]);
    }

    public function validationMessageDataProvider()
    {
        return [
            'name_required' => [
                'name', '', self::getValidationMessage(\App\Http\Requests\User\RegisterRequest::class, 'name.required')
            ],
            'email_required' => [
                'email', '', self::getValidationMessage(\App\Http\Requests\User\RegisterRequest::class, 'email.required')
            ],
            'password_min' => [
                'password', '12345', self::getValidationMessage(\App\Http\Requests\User\RegisterRequest::class, 'password.min')
            ],
            'password_confirmed' => [
                'password', 'wrongpassword', self::getValidationMessage(\App\Http\Requests\User\RegisterRequest::class, 'password.confirmed')
            ],
            'password_required' => [
                'password', '', self::getValidationMessage(\App\Http\Requests\User\RegisterRequest::class, 'password.required')
            ],
        ];
    }

    //会員情報登録
    public function test_register_user(){
        $response = $this->post('/register',[
            'name' => "テストユーザ",
            'email' => "test@gmail.com",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas( 'users', [
            'name' => "テストユーザ",
            'email' => "test@gmail.com",
        ]);
    }
}
