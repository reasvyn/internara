<?php

declare(strict_types=1);

namespace Modules\Status\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;

/**
 * Notification sent when an account's status changes.
 * Informs user about status transitions and any required actions.
 */
class AccountStatusChanged extends Notification
{
    public function __construct(
        public User $user,
        public Status $oldStatus,
        public Status $newStatus,
        public ?string $reason = null,
        public ?User $changedBy = null,
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
        $subject = match ($this->newStatus) {
            Status::VERIFIED => '✅ Akun Anda Telah Diverifikasi',
            Status::SUSPENDED => '🚫 Akun Anda Disuspensi',
            Status::RESTRICTED => '⚠️ Akun Anda Dibatasi',
            Status::INACTIVE => '😴 Akun Anda Menjadi Tidak Aktif',
            Status::ARCHIVED => '📦 Akun Anda Diarsipkan',
            default => '📝 Status Akun Berubah',
        };

        return new MailMessage()
            ->subject($subject)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Status akun Anda telah berubah:')
            ->line('**Status Lama:** '.$this->oldStatus->label())
            ->line('**Status Baru:** '.$this->newStatus->label())
            ->when($this->reason, fn ($mail) => $mail->line('**Alasan:** '.$this->reason))
            ->when(
                $this->changedBy,
                fn ($mail) => $mail->line('**Diubah oleh:** '.$this->changedBy->name),
            )
            ->action('Lihat Detail Akun', url('/account/status'))
            ->line('Jika Anda memiliki pertanyaan, hubungi tim dukungan kami.');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'reason' => $this->reason,
            'changed_by_user_id' => $this->changedBy?->id,
            'changed_by_name' => $this->changedBy?->name,
            'action_url' => '/account/status',
        ];
    }
}
