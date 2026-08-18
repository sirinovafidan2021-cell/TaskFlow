<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Projects\Models\Project;

class NewProjectCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly User $creator,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('A New Project Has Been Created')
            ->greeting('Hello '.($notifiable->name ?: 'there').',')
            ->line('A new project has been created in Task Flow.')
            ->line('Project: '.$this->project->name);

        if ($this->project->description) {
            $message->line('Description: '.$this->project->description);
        }

        return $message
            ->line('Created by: '.$this->creator->name)
            ->line('Created on: '.$this->project->created_at?->format('F j, Y'))
            ->action('View Project', route('projects.show', $this->project))
            ->line('Log in to Task Flow to view the project.');
    }
}
