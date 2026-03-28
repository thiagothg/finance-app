<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Household;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HouseholdInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Household $household,
        private string $inviterName,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $acceptInvitationUrl = sprintf(
            '%s/invite/accept?%s',
            $frontendUrl,
            http_build_query([
                'code' => $this->household->invitation_code,
                'email' => $notifiable->email,
            ])
        );

        return (new MailMessage)
            ->subject("You've been invited to join {$this->household->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->inviterName} has invited you to join their household \"{$this->household->name}\".")
            ->line('Please click the button below to finish creating your account and accept the invitation.')
            ->action('Accept Invitation', $acceptInvitationUrl)
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }
}
