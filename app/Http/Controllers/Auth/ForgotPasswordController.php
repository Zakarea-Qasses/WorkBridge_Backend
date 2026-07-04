<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($data['email']);
        $rateKey = 'forgot-password:' . $request->ip() . ':' . $email;

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return response()->json([
                'message' => 'Too many password reset requests. Please try again later.',
            ], 429);
        }

        if (! User::where('email', $email)->exists()) {
            RateLimiter::hit($rateKey, 600);

            return response()->json([
                'message' => 'No account was found for this email address.',
            ], 404);
        }

        RateLimiter::hit($rateKey, 600);

        $status = Password::sendResetLink([
            'email' => $email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Could not send the password reset link. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'Password reset link sent to your email.',
        ]);
    }
}
