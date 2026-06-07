<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $rateKey = 'reset-password:' . $request->ip() . ':' . strtolower($data['email']);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'message' => 'تم تجاوز عدد المحاولات، يرجى المحاولة لاحقاً'
            ], 429);
        }

        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $request->password_confirmation,
                'token' => $data['token'],
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ]);

                $user->tokens()->delete();

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            RateLimiter::clear($rateKey);

            return response()->json([
                'message' => 'تم إعادة تعيين كلمة المرور بنجاح'
            ], 200);
        }

        RateLimiter::hit($rateKey, 600); // زيادة عدد المحاولات الفاشلة

        return response()->json([
            'message' => 'رابط إعادة التعيين غير صحيح أو منتهي الصلاحية'
        ], 400);
    }
}