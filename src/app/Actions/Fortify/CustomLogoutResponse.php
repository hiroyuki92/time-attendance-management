<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LogoutResponse;

class CustomLogoutResponse implements LogoutResponse
{
    /**
     * Redirect the user after logging out.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toResponse($request)
    {
        return redirect('/login'); // ログアウト後にログイン画面にリダイレクト
    }
}