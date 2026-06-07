<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email']
        ]);

        $rateKey = 'forgot-password:' . $request->ip() . ':' . strtolower($data['email']);

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return response()->json([
                'message' => 'تم إرسال طلبات كثيرة، يرجى المحاولة لاحقاً'
            ], 429);
        }

        RateLimiter::hit($rateKey, 600); // 3 محاولات كل 10 دقائق

        $status = Password::sendResetLink([
            'email' => $data['email']
        ]);

        /*
         مهم:
         نرجع نفس الرسالة حتى لو الإيميل غير موجود
         حتى ما نكشف إذا الحساب موجود أو لا
        */
        return response()->json([
            'message' => 'إذا كان البريد مسجلاً لدينا، سيتم إرسال رابط إعادة تعيين كلمة المرور'
        ], 200);
    }
}