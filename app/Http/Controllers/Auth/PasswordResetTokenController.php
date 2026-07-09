<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordResetTokenController extends Controller
{
    public function show(Request $request, string $token)
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $query = http_build_query([
            'token' => $token,
            'email' => $request->query('email'),
        ]);

        return redirect()->away($frontendUrl . '/reset-password?' . $query);
    }
}
