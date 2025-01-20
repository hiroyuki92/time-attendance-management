<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function index()
    {
        return view('admin.admin_request_index');
    }

    public function show()
    {
        return view('admin.admin_request_approval');
    }
}
