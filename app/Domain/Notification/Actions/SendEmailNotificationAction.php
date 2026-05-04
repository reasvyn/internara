<?php

declare(strict_types=1);

namespace App\Domain\Notification\Actions;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Stateless Action to send email notification.
 *
 * S1 - Secure: Validates email configuration.
 * S2 - Sustain: Uses Laravel's mail system.
 */
class SendEmailNotificationAction
{
    public function execute(
        string $userId,
        string $subject,
        string $body,
        ?string $view = null,
        array $data = [],
    ): void {
        $user = User::findOrFail($userId);

        if (! $user->email) {
            throw new \InvalidArgumentException('User does not have an email address.');
        }

        $view = $view ?? 'emails.notification';

        Mail::send(
            $view,
            array_merge($data, [
                'user' => $user,
                'subject' => $subject,
                'body' => $body,
            ]),
            function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)->subject($subject);
            },
        );
    }
}
