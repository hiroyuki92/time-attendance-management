<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 認証されていない場合はログインページへリダイレクト
        if (!Auth::check()) {
            return redirect('/login');
        }

        // ログイン済みでも管理者でなければ403エラーを返す
        if (Auth::user()->role !== 'admin') {
            abort(403, '管理者権限が必要です。');
        }

        return $next($request);
    }
}
