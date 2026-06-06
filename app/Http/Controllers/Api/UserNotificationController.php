<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    /**
     * عرض إشعارات المستخدم الحالي
     */
    public function index(Request $request)
    {
        $notifications = UserNotification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'notifications' => $notifications
        ], 200);
    }

    /**
     * عدد الإشعارات غير المقروءة
     */
    public function unreadCount(Request $request)
    {
        $count = UserNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $count
        ], 200);
    }

    /**
     * تعليم إشعار واحد كمقروء
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = UserNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'الإشعار غير موجود'
            ], 404);
        }

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now()
            ]);
        }

        return response()->json([
            'message' => 'تم تعليم الإشعار كمقروء',
            'notification' => $notification->fresh()
        ], 200);
    }

    /**
     * تعليم كل الإشعارات كمقروءة
     */
    public function markAllAsRead(Request $request)
    {
        $updatedCount = UserNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now()
            ]);

        return response()->json([
            'message' => 'تم تعليم جميع الإشعارات كمقروءة',
            'updated_count' => $updatedCount
        ], 200);
    }

    /**
     * حذف إشعار واحد - اختياري لكنه مفيد
     */
    public function destroy(Request $request, $id)
    {
        $notification = UserNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'الإشعار غير موجود'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'تم حذف الإشعار بنجاح'
        ], 200);
    }
}