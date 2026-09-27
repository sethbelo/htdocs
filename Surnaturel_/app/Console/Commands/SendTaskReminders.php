<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskAssigned;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('erp:task-reminders')]
#[Description('Envoie les rappels de tâches arrivés à échéance')]
class SendTaskReminders extends Command
{
    public function handle(): int
    {
        $tasks = Task::query()->with('assignee')->whereNotNull('remind_at')->whereNull('reminded_at')
            ->where('remind_at', '<=', now())->where('status', '!=', 'done')->get();

        foreach ($tasks as $task) {
            $task->assignee?->notify(new TaskAssigned($task, isReminder: true));
            $task->update(['reminded_at' => now()]);
        }

        $this->info("{$tasks->count()} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
