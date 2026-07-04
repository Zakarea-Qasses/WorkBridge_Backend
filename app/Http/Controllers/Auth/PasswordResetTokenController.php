<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordResetTokenController extends Controller
{
    public function show(Request $request, string $token)
    {
        return response()->json([
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }
}
