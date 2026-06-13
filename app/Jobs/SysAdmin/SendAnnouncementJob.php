<?php

declare(strict_types=1);

namespace App\Jobs\SysAdmin;

use App\SysAdmin\Announcement\Actions\SendAnnouncementAction;
use App\SysAdmin\Announcement\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [2, 10, 30];

    public function __construct(
        protected readonly string $announcementId,
    ) {}

    public function handle(SendAnnouncementAction $sendAnnouncement): void
    {
        $announcement = Announcement::findOrFail($this->announcementId);

        $sendAnnouncement->sendNotifications($announcement, [
            'target_roles' => $announcement->target_roles,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('Announcement sending failed', [
            'announcement_id' => $this->announcementId,
            'error' => $e->getMessage(),
        ]);
    }
}
