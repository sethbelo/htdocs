<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task, public bool $isReminder = false) {}

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
        return [
            'title' => $this->isReminder ? 'Rappel de tâche' : 'Nouvelle tâche',
            'message' => $this->task->title.($this->task->due_at ? ' — échéance '.$this->task->due_at->format('d/m H:i') : ''),
            'url' => route('tasks.index'),
            'icon' => 'clipboard-document-check',
        ];
    }
}
