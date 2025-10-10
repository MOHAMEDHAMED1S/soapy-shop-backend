<?php

namespace App\Mail;

use App\Models\AdminNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public AdminNotification $notification;

    /**
     * Create a new message instance.
     */
    public function __construct(AdminNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $priority = $this->notification->payload['priority'] ?? 'medium';
        $subject = "[{$priority}] " . $this->notification->payload['title'];

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-notification',
            with: [
                'notification' => $this->notification,
                'title' => $this->notification->payload['title'],
                'message' => $this->notification->payload['message'],
                'priority' => $this->notification->payload['priority'] ?? 'medium',
                'data' => $this->notification->payload['data'] ?? [],
                'createdAt' => $this->notification->created_at->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
