<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BlockchainHealthAlert extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private array $alerts)
    {
    }

    /**
     * Get the notification's delivery channels.
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
        $message = (new MailMessage)
            ->subject('⚠️ Blockchain Health Alert')
            ->line('The following blockchain network issues have been detected:');

        foreach ($this->alerts as $alert) {
            $message->line("Network: {$alert['network']}");
            foreach ($alert['issues'] as $issue) {
                $message->line("- {$issue}");
            }
            $message->line('---');
        }

        return $message
            ->line('Please investigate these issues as soon as possible.')
            ->action('View Dashboard', url('/dashboard/blockchain/health'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alerts' => $this->alerts,
            'timestamp' => now()
        ];
    }
}
