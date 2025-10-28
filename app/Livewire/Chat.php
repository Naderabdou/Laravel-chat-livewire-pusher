<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chat extends Component
{
    public $users;

    public $selectedUser;

    public $newMessage;

    public $messages;

    public $loginID;

    public function mount()
    {
        $this->users = User::whereNot('id', auth()->id())->latest()->get();
        $this->selectedUser = $this->users->first();
        $this->loadMessages();
        $this->loginID = Auth::id();

    }

    public function selectUser($id)
    {
        $this->selectedUser = User::find($id);
        $this->loadMessages();
    }

    public function loadMessages()
    {
        $this->messages = ChatMessage::query()
            ->where(function ($query) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $this->selectedUser->id);
            })
            ->orWhere(function ($query) {
                $query->where('sender_id', $this->selectedUser->id)
                    ->where('receiver_id', auth()->id());
            })
            ->get();
    }

    public function submit()
    {
        if (! $this->newMessage) {
            return;
        }
        $message = ChatMessage::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $this->selectedUser->id,
            'message' => $this->newMessage,
        ]);
        $this->messages->push($message);
        $this->newMessage = '';
        broadcast(new MessageSent($message));
    }

    public function getListeners()
    {
        return [
            "echo-private:chat.{$this->loginID},MessageSent" => 'newChatMessageNotification',
        ];
    }

    public function newChatMessageNotification($message)
    {
        if ($message['sender_id'] == $this->selectedUser->id) {
            $messageObject = ChatMessage::find($message['id']);
            $this->messages->push($messageObject);
        }
    }

    public function updatedNewMessage($value)
    {
        $this->dispatch("userTyping", userID : $this->loginID , userName : Auth::user()->name , selectedUserID : $this->selectedUser->id);
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
