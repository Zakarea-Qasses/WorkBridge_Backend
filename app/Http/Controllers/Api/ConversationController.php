<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ConversationController extends Controller
{
    /**
     * بدء محادثة جديدة أو جلب محادثة موجودة
     */
    public function start(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $authId = $request->user()->id;
        $otherId = (int) $data['user_id'];

        if ($authId === $otherId) {
            return response()->json([
                'message' => 'لا يمكنك إنشاء محادثة مع نفسك',
            ], 422);
        }

        $user1 = min($authId, $otherId);
        $user2 = max($authId, $otherId);

        $conversation = Conversation::firstOrCreate([
            'user1_id' => $user1,
            'user2_id' => $user2,
        ]);

        return response()->json([
            'message' => 'تم فتح المحادثة بنجاح',
            'conversation' => $conversation->load([
                'user1:id,name',
                'user2:id,name'
            ]),
        ], 200);
    }

    /**
     * عرض محادثات المستخدم الحالي
     */
    public function myConversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                    ->orWhere('user2_id', $userId);
            })
            ->with([
                'user1:id,name',
                'user2:id,name',
                'messages' => function ($query) {
                    $query->latest()->limit(1);
                },
                'messages.sender:id,name',
            ])
            ->withCount([
                'messages as unread_count' => function ($query) use ($userId) {
                    $query->where('sender_id', '!=', $userId)
                          ->whereNull('read_at');
                }
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'conversations' => $conversations,
        ], 200);
    }

    /**
     * عرض رسائل محادثة معينة
     */
    public function messages(Request $request, Conversation $conversation)
    {
        $this->checkUserInConversation($request, $conversation);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->oldest()
            ->paginate(20);

        return response()->json([
            'messages' => $messages,
        ], 200);
    }

    /**
     * إرسال رسالة داخل محادثة
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->checkUserInConversation($request, $conversation);

        $user = $request->user();

        $rateKey = 'send-message:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateKey, 20)) {
            return response()->json([
                'message' => 'لقد أرسلت رسائل كثيرة، يرجى الانتظار قليلاً'
            ], 429);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'type' => ['nullable', 'in:text,image,file'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $data['content'],
            'type' => $data['type'] ?? 'text',
        ]);

        $conversation->update([
            'last_message_at' => now(),
        ]);

        RateLimiter::hit($rateKey, 60);

        return response()->json([
            'message' => 'تم إرسال الرسالة بنجاح',
            'data' => $message->load('sender:id,name'),
        ], 201);
    }
    /**
     * تعليم رسائل المحادثة كمقروءة
     */
    public function markAsRead(Request $request, Conversation $conversation)
    {
        $this->checkUserInConversation($request, $conversation);

        $updatedCount = $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'تم تعليم الرسائل كمقروءة',
            'updated_count' => $updatedCount,
        ], 200);
    }

    /**
     * التحقق أن المستخدم طرف في المحادثة
     */
    private function checkUserInConversation(Request $request, Conversation $conversation): void
    {
        $userId = $request->user()->id;

        if ($conversation->user1_id !== $userId && $conversation->user2_id !== $userId) {
            abort(403, 'غير مسموح لك بالدخول لهذه المحادثة');
        }
    }
}