<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffRegistrationInvite extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $email, public string $registrationUrl)
    {
        $this->connection = 'database';
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confam Buy staff registration invite')
            ->greeting('You have been invited as staff')
            ->line('An administrator added this email to the Confam Buy staff allowlist.')
            ->line('Register with this exact email to get staff access.')
            ->action('Complete registration', $this->registrationUrl)
            ->line('If you were not expecting this invite, you can ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'email' => $this->email,
            'registration_url' => $this->registrationUrl,
        ];
    }
}
