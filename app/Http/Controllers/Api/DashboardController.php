<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function personal(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'غير مصرح لك بالدخول إلى لوحة المستخدم الشخصي'
            ], 403);
        }

        return response()->json([
            'message' => 'مرحباً بك في لوحة تحكم المستخدم الشخصي',
            'role' => $user->role,
            'user' => $user,
        ], 200);
    }

    public function company(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'غير مصرح لك بالدخول إلى لوحة الشركة'
            ], 403);
        }

        return response()->json([
            'message' => 'مرحباً بك في لوحة تحكم الشركة',
            'role' => $user->role,
            'user' => $user,
        ], 200);
    }

    public function admin(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'غير مصرح لك بالدخول إلى لوحة الأدمن'
            ], 403);
        }

        return response()->json([
            'message' => 'مرحباً بك في لوحة تحكم الأدمن',
            'role' => $user->role,
            'user' => $user,
        ], 200);
    }
}