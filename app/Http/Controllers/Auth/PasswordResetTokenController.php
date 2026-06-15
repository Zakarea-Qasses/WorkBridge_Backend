<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class PasswordResetTokenController extends Controller
{
    public function show(string $token)
    {
        return response()->json([
            'token' => $token,
        ]);
    }
}
