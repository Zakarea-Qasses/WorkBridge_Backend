<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserSettingController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'settings' => $this->settings($request),
        ]);
    }

    public function updatePrivacy(Request $request)
    {
        $data = $request->validate([
            'profile_visible' => ['required', 'boolean'],
            'contact_permission' => ['required', Rule::in(['all', 'verified', 'none'])],
        ]);

        $settings = $this->settingsModel($request);
        $settings->update($data);

        return response()->json([
            'message' => 'تم تحديث إعدادات الخصوصية بنجاح.',
            'settings' => $this->settings($request),
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $data = $request->validate([
            'message_notifications' => ['required', 'boolean'],
        ]);

        $settings = $this->settingsModel($request);
        $settings->update($data);

        return response()->json([
            'message' => 'تم تحديث إعدادات الإشعارات بنجاح.',
            'settings' => $this->settings($request),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور الحالية غير صحيحة.',
            ], 422);
        }

        $user->update([
            'password' => $data['password'],
        ]);

        $user->tokens()
            ->where('id', '!=', $request->user()->currentAccessToken()?->id)
            ->delete();

        return response()->json([
            'message' => 'تم تحديث كلمة المرور بنجاح.',
        ]);
    }

    public function clearLocalData(Request $request)
    {
        $deletedNotifications = UserNotification::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'تم حذف بيانات المستخدم المحلية بنجاح.',
            'deleted_notifications' => $deletedNotifications,
        ]);
    }

    private function settings(Request $request): array
    {
        $settings = $this->settingsModel($request);

        return [
            'privacy' => [
                'profile_visible' => $settings->profile_visible,
                'contact_permission' => $settings->contact_permission,
            ],
            'notifications' => [
                'message_notifications' => $settings->message_notifications,
            ],
        ];
    }

    private function settingsModel(Request $request)
    {
        return $request->user()->settings()->firstOrCreate([
            'user_id' => $request->user()->id,
        ], [
            'profile_visible' => true,
            'contact_permission' => 'all',
            'message_notifications' => true,
        ]);
    }
}
