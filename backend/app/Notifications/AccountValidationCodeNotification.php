<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountValidationCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $code,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Account Validation Code')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Use the code below to validate your account:')
            ->line("**{$this->code}**")
            ->line('This code will expire in 15 minutes.')
            ->line('If you did not request this code, please ignore this email.');
    }
}
