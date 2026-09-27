<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class MentionedInComment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comment $comment, public User $author) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $subject = $this->comment->commentable;

        return [
            'title' => "{$this->author->name} vous a mentionné",
            'message' => Str::limit($this->comment->body, 120),
            'url' => $subject instanceof Order ? route('orders.show', $subject) : route('home'),
            'icon' => 'at-symbol',
        ];
    }
}
