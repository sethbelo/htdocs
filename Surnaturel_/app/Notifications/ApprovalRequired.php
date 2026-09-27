<?php

namespace App\Notifications;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ApprovalRequest $request, public string $title, public string $stepName) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Validation requise : {$this->title}")
            ->line("« {$this->title} » attend votre validation (étape : {$this->stepName}).")
            ->action('Ouvrir mes validations', route('approvals.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Validation requise',
            'message' => "{$this->title} — étape : {$this->stepName}",
            'url' => route('approvals.index'),
            'icon' => 'check-badge',
        ];
    }
}
