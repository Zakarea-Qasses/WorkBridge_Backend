<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerificationOtp;
use App\Models\UserNotification;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Verified;

class AuthController extends Controller
{
    /**
     * تسجيل مستخدم جديد
     */
    public function register(Request $request)
    {
        $rateKey = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'message' => 'تم تسجيل محاولات كثيرة، يرجى الانتظار'
            ], 429);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'role' => ['required', 'in:personal,company'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], 
            'role' => $data['role'],
            'status' => 'pending_review',
        ]);

        if ($user->role === 'personal') {
            $user->profile()->create(['name' => $user->name]);
        } elseif ($user->role === 'company') {
            $user->company()->create(['company_name' => $user->name]);
        }
        
        $user->wallet()->create([
            'balance'=>0,
            'currency'=>'USD',
            'status'=>'active'
        ]);

        $otp = rand(100000, 999999);
        EmailVerificationOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        try {
            $user->notify(new VerifyEmailOtpNotification($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send OTP notification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        // إشعار الأدمن بحساب جديد
        if ($user->role !== 'admin') {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                UserNotification::create([
                    'user_id' => $admin->id,
                    'type' => 'new_account_review',
                    'title' => 'حساب جديد بانتظار المراجعة',
                    'message' => $user->name . ' قام بإنشاء حساب جديد ويحتاج إلى مراجعة.',
                ]);
            }
        }

        RateLimiter::hit($rateKey, 600); // 10 دقائق cooldown

        return response()->json([
            'message' => 'تم إنشاء الحساب. يرجى تأكيد البريد الإلكتروني.',
            'user' => $user,
        ], 201);
    }

    /**
     * تسجيل الدخول
     */
    public function login(Request $request)
    {
        $rateKey = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'message' => 'تم تجاوز عدد محاولات تسجيل الدخول، يرجى الانتظار'
            ], 429);
        }

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($rateKey, 600);
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'يجب تأكيد البريد الإلكتروني أولاً'], 403);
        }

        if ($user->status === 'blocked') {
            return response()->json(['message' => 'تم حظر هذا الحساب من قبل الإدارة'], 403);
        }
        if ($user->role !== 'admin' && $user->status !== 'active') {
            return response()->json(['message' => 'حسابك بانتظار مراجعة الإدارة'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $dashboardUrl = match ($user->role) {
            'personal' => '/api/dashboard/personal',
            'company' => '/api/dashboard/company',
            'admin' => '/api/dashboard/admin',
            default => null,
        };

        if (!$dashboardUrl) {
            return response()->json(['message' => 'نوع المستخدم غير معروف'], 403);
        }

        RateLimiter::clear($rateKey);

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'user' => $user,
            'dashboard' => [
                'role' => $user->role,
                'url' => $dashboardUrl,
            ],
        ], 200);
    }

    /**
     * تأكيد البريد الإلكتروني باستخدام OTP
     */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user) return response()->json(['message' => 'المستخدم غير موجود'], 404);
        if ($user->hasVerifiedEmail()) return response()->json(['message' => 'البريد مفعل مسبقاً']);

        $rateKey = 'verify-otp:' . $request->ip() . ':' . $user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json(['message' => 'تم تجاوز عدد المحاولات، يرجى الانتظار'], 429);
        }

        $otpRecord = EmailVerificationOtp::where('user_id', $user->id)->first();
        if (!$otpRecord) return response()->json(['message' => 'لا يوجد كود تحقق'], 404);
        if ($otpRecord->expires_at->isPast()) return response()->json(['message' => 'انتهت صلاحية الكود'], 403);

        if (!Hash::check($data['otp'], $otpRecord->otp)) {
            Log::warning('OTP verification failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);
            RateLimiter::hit($rateKey, 600);
            return response()->json(['message' => 'كود التحقق غير صحيح'], 403);
        }

        $user->markEmailAsVerified();
        $otpRecord->delete();
        RateLimiter::clear($rateKey);

        event(new Verified($user));

        return response()->json(['message' => 'تم تأكيد البريد الإلكتروني بنجاح'], 200);
    }

    /**
     * إعادة إرسال OTP
     */
    public function resend(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'المستخدم غير موجود'], 404);
        if ($user->hasVerifiedEmail()) return response()->json(['message' => 'البريد مفعل مسبقاً']);

        $rateKey = 'resend-otp:' . $request->ip() . ':' . $user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return response()->json(['message' => 'تم إرسال أكواد كثيرة، يرجى المحاولة لاحقاً'], 429);
        }

        $otpRecord = EmailVerificationOtp::where('user_id', $user->id)->first();
        if ($otpRecord && $otpRecord->created_at->gt(now()->subMinute())) {
            return response()->json(['message' => 'يرجى الانتظار دقيقة قبل طلب كود جديد'], 429);
        }

        $otp = rand(100000, 999999);
        EmailVerificationOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        try {
            $user->notify(new VerifyEmailOtpNotification($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send OTP notification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'تم إنشاء الحساب لكن فشل إرسال كود التحقق'], 500);
        }

        RateLimiter::hit($rateKey, 600);

        return response()->json(['message' => 'تم إرسال كود تحقق جديد'], 200);
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'تم تسجيل الخروج بنجاح'], 200);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $request->user()?->id,
                'email' => $request->user()?->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'حدث خطأ أثناء تسجيل الخروج'], 500);
        }
    }
}