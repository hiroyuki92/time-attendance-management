<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, '管理者権限が必要です。');
        }

        $users = User::where('role', 'user')->get();
        return view('admin.admin_staff_list', compact('users'));
    }

}
