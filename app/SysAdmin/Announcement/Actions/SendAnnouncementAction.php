<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Models\Announcement;
use Illuminate\Support\Facades\Validator;

final class SendAnnouncementAction extends BaseCommandAction
{
    public function __construct(
        private readonly SendAnnouncementNotificationsAction $sendNotifications,
    ) {}

    public function execute(array $data): Announcement
    {
        $validated = Validator::validate($data, [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'required|in:info,success,warning,error',
            'status' => 'nullable|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date|after_or_equal:now',
            'link' => 'nullable|string|max:500',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string|exists:roles,name',
        ]);

        $status = isset($validated['status'])
            ? AnnouncementStatus::from($validated['status'])
            : AnnouncementStatus::default();

        return $this->transaction(function () use ($validated, $status) {
            $announcement = Announcement::create([
                'title' => $validated['title'],
                'message' => $validated['message'],
                'type' => $validated['type'],
                'status' => $status,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'link' => $validated['link'] ?? null,
                'target_roles' => $validated['target_roles'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if ($status === AnnouncementStatus::PUBLISHED) {
                $this->sendNotifications->execute($announcement, $validated);
            }

            $this->log('announcement_sent', $announcement, [
                'title' => $validated['title'],
                'status' => $status->value,
                'target_roles' => $validated['target_roles'] ?? 'all',
            ]);

            return $announcement;
        });
    }
}
