<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Core\Actions\LogAuditAction;
use App\Events\Internship\InternshipCreated;
use App\Notifications\InternshipCreatedNotification;

/**
 * Handles side effects when an internship is created.
 *
 * S2 - Sustain: Separates concerns, keeps Action focused on core logic.
 * S3 - Scalable: Easy to add/remove side effects without touching Action.
 */
class SendInternshipCreatedNotifications
{
    public function __construct(
        private readonly LogAuditAction $logAudit,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(InternshipCreated $event): void
    {
        // Log the audit trail
        $this->logAudit->execute(
            action: 'internship_created',
            subjectType: $event->internship::class,
            subjectId: $event->internship->id,
            payload: [
                'name' => $event->internship->name,
                'created_by' => $event->createdBy->email,
            ],
            module: 'Internship'
        );

        // Notify relevant stakeholders (example)
        // $event->internship->company->notify(new InternshipCreatedNotification($event->internship));
    }
}
