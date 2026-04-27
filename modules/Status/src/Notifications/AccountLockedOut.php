<?php

declare(strict_types=1);

namespace Modules\Status\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\User\Models\User;

/**
 * Notification sent when an account is locked out due to failed login attempts.
 */
class AccountLockedOut extends Notification
{
    public function __construct(
        public User $user,
        public int $failedAttempts,
        public int $lockoutMinutes,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable)
    {
        return new MailMessage()
            ->subject('⛔ Akun Anda Dikunci untuk Keamanan')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line(
                'Akun Anda telah dikunci setelah ' .
                    $this->failedAttempts .
                    ' percobaan login yang gagal.',
            )
            ->line('Akun akan otomatis dibuka dalam ' . $this->lockoutMinutes . ' menit.')
            ->line('Jika ini bukan Anda, segera ubah kata sandi Anda dan hubungi tim dukungan.')
            ->action('Hubungi Dukungan', url('/support'))
            ->line('Kami hanya melakukan ini untuk melindungi akun Anda.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'failed_attempts' => $this->failedAttempts,
            'lockout_minutes' => $this->lockoutMinutes,
            'action_url' => '/account/security',
        ];
    }
}
