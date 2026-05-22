<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function personal(Request $request)
    {
        return response()->json([
            'message' => 'مرحباً بك في لوحة تحكم المستخدم الشخصي',
            'role' => $request->user()->role,
            'user' => $request->user(),
        ]);
    }

    public function company(Request $request)
    {
        return response()->json([
            'message' => 'مرحباً بك في لوحة تحكم الشركة',
            'role' => $request->user()->role,
            'user' => $request->user(),
        ]);
    }

    public function admin(Request $request)
    {
        return response()->json([
            'message' => 'مرحباً بك في لوحة تحكم الأدمن',
            'role' => $request->user()->role,
            'user' => $request->user(),
        ]);
    }
}