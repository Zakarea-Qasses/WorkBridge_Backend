<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower($data['email']);
        $rateKey = 'reset-password:' . $request->ip() . ':' . $email;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'message' => 'Too many reset attempts. Please try again later.',
            ], 429);
        }

        $status = Password::reset(
            [
                'email' => $email,
                'password' => $data['password'],
                'password_confirmation' => $request->password_confirmation,
                'token' => $data['token'],
            ],
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            RateLimiter::clear($rateKey);

            return response()->json([
                'message' => 'Password reset successfully.',
            ]);
        }

        RateLimiter::hit($rateKey, 600);

        $message = $status === Password::INVALID_USER
            ? 'No account was found for this email address.'
            : 'The reset token is invalid or has expired.';

        return response()->json([
            'message' => $message,
        ], 400);
    }
}
