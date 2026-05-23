<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationOtp;
use App\Models\User;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\Fluent\Concerns\Has;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'role' => ['required', 'in:personal,company,admin'],
           
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
      
        ]);
        

        if($user->role==='personal'){
            $user->profile()->create([
                'name'=>$request->name
            ]);
          
        }

        elseif($user->role==='company'){
            $user->company()->create([
                'company_name'=>$user->name,
            ]);
        }

        $otp=rand(100000,999999);

        EmailVerificationOtp::updateOrCreate(
            ['user_id'=>$user->id],
            [
                'otp'=>Hash::make($otp),
                'expires_at'=>now()->addMinutes(10),
            ]
        );
        
        $user->notify(new VerifyEmailOtpNotification($otp));

        return response()->json([
            'message' => 'تم إنشاء الحساب. يرجى تأكيد البريد الإلكتروني.',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'يجب تأكيد البريد الإلكتروني أولاً'
            ], 403);
        }

      $token = $user->createToken('api-token')->plainTextToken;

$dashboardUrl = match ($user->role) {
    'personal' => '/api/dashboard/personal',
    'company' => '/api/dashboard/company',
    'admin' => '/api/dashboard/admin',
    default => null,
};

if (!$dashboardUrl) {
    return response()->json([
        'message' => 'نوع المستخدم غير معروف'
    ], 403);
}

return response()->json([
    'message' => 'تم تسجيل الدخول بنجاح',
    'token' => $token,
    'user' => $user,

    'dashboard' => [
        'role' => $user->role,
        'url' => $dashboardUrl,
    ]
]);
    }

public function verify(Request $request)
{
    $data = $request->validate([
        'email' => ['required', 'email'],
        'otp' => ['required', 'digits:6'],
    ]);

    $user = User::where('email', $data['email'])->first();

    if (!$user) {
        return response()->json([
            'message' => 'المستخدم غير موجود'
        ], 404);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'البريد مفعل مسبقاً'
        ]);
    }

    $otpRecord = EmailVerificationOtp::where('user_id', $user->id)->first();

    if (!$otpRecord) {
        return response()->json([
            'message' => 'لا يوجد كود تحقق'
        ], 404);
    }

    if ($otpRecord->expires_at->isPast()) {
        return response()->json([
            'message' => 'انتهت صلاحية الكود'
        ], 403);
    }

    if (!Hash::check($data['otp'], $otpRecord->otp)) {
        return response()->json([
            'message' => 'كود التحقق غير صحيح'
        ], 403);
    }

    $user->markEmailAsVerified();

    $otpRecord->delete();

    event(new Verified($user));

    return response()->json([
        'message' => 'تم تأكيد البريد الإلكتروني بنجاح'
    ]);
}

    public function resend(Request $request)
{
    $request->validate([
        'email' => ['required', 'email']
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'المستخدم غير موجود'
        ], 404);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'البريد مفعل مسبقاً'
        ]);
    }

    $otp=rand(100000,999999);

    EmailVerificationOtp::updateOrCreate(
        ['user_id'=>$user->id],
        [
            'otp'=>Hash::make($otp),
            'expires_at'=>now()->addMinutes(10)
        ]
    );

    $user->notify(new VerifyEmailOtpNotification($otp));

    return response()->json([
        'message' => 'تم إرسال كود تحقق جديد'
    ]);
}
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج'
        ]);
    }
}