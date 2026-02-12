<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public SupportTicket $ticket, public SupportTicketMessage $message)
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'New support ticket message';
        $ticketLabel = $this->ticket->ticket_number;

        return (new MailMessage)
            ->subject($subject.' ('.$ticketLabel.')')
            ->greeting('New ticket message')
            ->line('Ticket '.$ticketLabel.' has a new message.')
            ->line('Subject: '.$this->ticket->subject)
            ->line('Message: '.str($this->message->message)->limit(140));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'message_id' => $this->message->id,
            'message' => $this->message->message,
        ];
    }
}
