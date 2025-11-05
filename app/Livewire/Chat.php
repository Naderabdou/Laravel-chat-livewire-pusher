<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chat extends Component
{
    public $users;

    public $selectedUser;

    public $newMessage = '';

    public $messages;

    public $loginID;

    protected ChatService $chatService;

    public function boot(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function mount()
    {
        $authId = Auth::id();
        $this->loginID = $authId;

        // تحميل المستخدمين مرتبين حسب آخر محادثة
        $this->users = $this->chatService->getUsersOrderedByLastMessage($authId);

        // تحميل آخر شات مفتوح
        $lastChat = $this->chatService->getLastChat($authId);

        if ($lastChat) {
            $this->selectedUser = $lastChat->sender_id === $authId
                ? $lastChat->receiver
                : $lastChat->sender;

            $this->loadMessages();
        } else {
            $this->selectedUser = $this->users->first();
            $this->loadMessages(); 

        }
    }

    public function selectUser($id)
    {
        $this->selectedUser = User::findOrFail($id);
        $this->loadMessages();
        $this->dispatch('scrollToBottom');
    }

    public function loadMessages()
    {
        if (! $this->selectedUser) {
            $this->messages = collect();

            return;
        }

        $this->messages = $this->chatService->getMessagesBetween(
            Auth::id(),
            $this->selectedUser->id
        );
    }

    public function submit()
    {
        if (! trim($this->newMessage)) {
            return;
        }

        $message = ChatMessage::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->selectedUser->id,
            'message' => trim($this->newMessage),
        ]);

        $this->messages->push($message);
        $this->newMessage = '';
        $this->dispatch('scrollToBottom');

        // إعادة ترتيب المستخدمين
        $this->users = $this->chatService->getUsersOrderedByLastMessage(Auth::id());

        broadcast(new MessageSent($message));
    }

    public function getListeners()
    {
        return [
            "echo-private:chat.{$this->loginID},MessageSent" => 'onMessageReceived',
        ];
    }

    public function onMessageReceived($message)
    {
        if ($this->selectedUser && $message['sender_id'] == $this->selectedUser->id) {
            $this->messages->push(ChatMessage::find($message['id']));
            $this->dispatch('scrollToBottom');
        }

        $this->users = $this->chatService->getUsersOrderedByLastMessage(Auth::id());
    }

    public function updatedNewMessage($value)
    {
        broadcast(new UserTyping(
            Auth::id(),
            Auth::user()->name,
            $this->selectedUser->id,
            strlen($value) > 0
        ));
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
