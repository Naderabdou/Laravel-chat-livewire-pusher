<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $userID;

    public $userName;

    public $receiverID;

    public $isTyping; // 👈 أضفناها

    public function __construct($userID, $userName, $receiverID, $isTyping)
    {
        $this->userID = $userID;
        $this->userName = $userName;
        $this->receiverID = $receiverID;
        $this->isTyping = $isTyping;

    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.'.$this->receiverID)];
    }

    public function broadcastWith(): array
    {
        return [
            'userID' => $this->userID,
            'userName' => $this->userName,
            'receiverID' => $this->receiverID,
            'isTyping' => $this->isTyping,

        ];
    }
}
