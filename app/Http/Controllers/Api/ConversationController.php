<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
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
            'conversation' => $conversation->load(['user1:id,name', 'user2:id,name']),
        ]);
    }

    public function myConversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where(function ($query) use ($userId) {
            $query->where('user1_id', $userId)
                ->orWhere('user2_id', $userId);
        })
            ->with(['user1:id,name', 'user2:id,name'])
            ->latest('last_message_at')
            ->latest()
            ->get();

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $this->checkUserInConversation($request, $conversation);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->oldest()
            ->paginate(20);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->checkUserInConversation($request, $conversation);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'type' => ['nullable', 'in:text,image,file'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'content' => $data['content'],
            'type' => $data['type'] ?? 'text',
        ]);

        $conversation->update([
            'last_message_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم إرسال الرسالة بنجاح',
            'data' => $message->load('sender:id,name'),
        ], 201);
    }

    private function checkUserInConversation(Request $request, Conversation $conversation): void
    {
        $userId = $request->user()->id;

        if ($conversation->user1_id !== $userId && $conversation->user2_id !== $userId) {
            abort(403, 'غير مسموح لك بالدخول لهذه المحادثة');
        }
    }
}
