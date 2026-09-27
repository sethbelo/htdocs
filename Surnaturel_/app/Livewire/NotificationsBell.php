<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationsBell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function open(string $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->redirect($notification->data['url'] ?? route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.notifications-bell', [
            'notifications' => auth()->user()->notifications()->latest()->limit(8)->get(),
            'unread' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
