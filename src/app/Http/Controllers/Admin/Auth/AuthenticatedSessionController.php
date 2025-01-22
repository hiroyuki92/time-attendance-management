<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    public function index(Request $request)
    {
        return view('auth.admin_login');
    }
}
