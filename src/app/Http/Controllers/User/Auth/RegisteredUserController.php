<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\User\RegisterRequest;

class RegisteredUserController extends Controller
{
    /**
     * ユーザー登録フォームを表示
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * 新しいユーザーを登録
     *
     * @param  \App\Http\Requests\RegisterRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(RegisterRequest $request)
    {
        $validatedData = $request->validated();
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

    try {
        $user->sendEmailVerificationNotification();
    } catch (\Exception $e) {
        \Log::error('Verification email could not be sent: ' . $e->getMessage());
    }

    auth()->login($user);

    return redirect()->route('verification.notice');
    }

    /**
     * メール認証ページの表示処理
     */
    public function showVerificationNotice()
    {
        if (Auth::check() && Auth::user()->hasVerifiedEmail()) {
            return redirect('/login');
        }

        return view('auth.verify-email');
    }

     /**
     * メール認証の再送処理
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            Auth::logout();
            return redirect('/login')->with('message', 'すでに認証が完了しています。ログインしてください。');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            Log::error('Verification email could not be sent: ' . $e->getMessage());
            return redirect()->route('verification.notice')->withErrors([
                'email' => '確認メールの送信に失敗しました。時間をおいて再試行してください。',
            ]);
        }

        return redirect()->route('verification.notice')->with('message', '確認メールを再送しました。');
    }
}
