<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProChat extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-chat';
    protected static ?string $title = 'Messagerie';

    public $activeUserId = null;
    public $searchQuery = '';
    public $newMessage = '';
    public $attachment = null;

    protected $queryString = [
        'activeUserId' => ['except' => null, 'as' => 'user'],
    ];

    public function mount()
    {
        if ($this->activeUserId) {
            $this->selectUser($this->activeUserId);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function selectUser($userId)
    {
        $this->activeUserId = $userId;
        $this->newMessage = '';
        $this->attachment = null;

        Message::where('sender_id', $this->activeUserId)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->dispatch('scroll-to-bottom');
    }

    public function sendMessage()
    {
        if (!$this->activeUserId) {
            return;
        }

        $this->validate([
            'newMessage' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // Max 10MB
        ]);

        if (trim($this->newMessage) === '' && !$this->attachment) {
            return;
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentType = null;

        if ($this->attachment) {
            $originalName = $this->attachment->getClientOriginalName();
            $path = $this->attachment->store('chat_attachments', 'public');
            
            $attachmentPath = $path;
            $attachmentName = $originalName;
            
            $mimeType = $this->attachment->getMimeType();
            $attachmentType = str_starts_with($mimeType, 'image/') ? 'image' : 'file';
        }

        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->activeUserId,
            'message' => $this->newMessage,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
            'is_read' => false,
        ]);

        $this->newMessage = '';
        $this->attachment = null;

        $this->dispatch('scroll-to-bottom');
    }

    public function deleteAttachment()
    {
        $this->attachment = null;
    }

    public function getActiveUserProperty()
    {
        if (!$this->activeUserId) {
            return null;
        }

        return User::find($this->activeUserId);
    }

    public function getUsersProperty()
    {
        $currentUserId = Auth::id();
        $schoolId = Auth::user()->school_id;

        $usersQuery = User::where('id', '!=', $currentUserId);
        
        if ($schoolId) {
            $usersQuery->where('school_id', $schoolId);
        }

        if ($this->searchQuery) {
            $usersQuery->where('name', 'like', '%' . $this->searchQuery . '%');
        }

        $users = $usersQuery->get();

        return $users->map(function ($user) use ($currentUserId) {
            $lastMessage = Message::where(function ($query) use ($currentUserId, $user) {
                $query->where('sender_id', $currentUserId)->where('receiver_id', $user->id);
            })->orWhere(function ($query) use ($currentUserId, $user) {
                $query->where('sender_id', $user->id)->where('receiver_id', $currentUserId);
            })->latest()->first();

            $unreadCount = Message::where('sender_id', $user->id)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->count();

            $user->last_message = $lastMessage;
            $user->unread_count = $unreadCount;
            $user->sort_time = $lastMessage ? $lastMessage->created_at->timestamp : 0;

            return $user;
        })->sortByDesc(function ($user) {
            return [$user->sort_time, $user->isOnline() ? 1 : 0, $user->name];
        });
    }

    public function getMessagesProperty()
    {
        if (!$this->activeUserId) {
            return collect();
        }

        $currentUserId = Auth::id();
        $activeId = $this->activeUserId;

        Message::where('sender_id', $activeId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return Message::where(function ($query) use ($currentUserId, $activeId) {
            $query->where('sender_id', $currentUserId)->where('receiver_id', $activeId);
        })->orWhere(function ($query) use ($currentUserId, $activeId) {
            $query->where('sender_id', $activeId)->where('receiver_id', $currentUserId);
        })->orderBy('created_at', 'asc')->get();
    }
}
