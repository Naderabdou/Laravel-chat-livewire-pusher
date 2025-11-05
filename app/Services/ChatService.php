<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    public function getUsersOrderedByLastMessage(int $authId): Collection
    {
        return User::query()
            ->where('id', '!=', $authId)
            ->select('users.*')
            ->addSelect([
                // نجيب آخر تاريخ رسالة بين المستخدم الحالي وكل مستخدم
                'last_message_at' => ChatMessage::select('created_at')
                    ->where(function ($query) use ($authId) {
                        $query->whereColumn('sender_id', 'users.id')
                            ->where('receiver_id', $authId);
                    })
                    ->orWhere(function ($query) use ($authId) {
                        $query->whereColumn('receiver_id', 'users.id')
                            ->where('sender_id', $authId);
                    })
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->get();
    }

    public function getLastChat(int $authId): ?ChatMessage
    {
        return ChatMessage::where(function ($query) use ($authId) {
            $query->where('sender_id', $authId)
                ->orWhere('receiver_id', $authId);
        })
            ->latest('created_at')
            ->first();
    }

    public function getMessagesBetween(int $authId, int $otherUserId)
    {
        return ChatMessage::where(function ($query) use ($authId, $otherUserId) {
            $query->where('sender_id', $authId)
                ->where('receiver_id', $otherUserId);
        })
            ->orWhere(function ($query) use ($authId, $otherUserId) {
                $query->where('sender_id', $otherUserId)
                    ->where('receiver_id', $authId);
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
