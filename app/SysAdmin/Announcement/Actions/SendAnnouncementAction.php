<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Actions;

use App\Core\Actions\BaseAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Models\Announcement;
use App\SysAdmin\Announcement\Notifications\AnnouncementNotification;
use App\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

final class SendAnnouncementAction extends BaseAction
{
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
                    $this->sendNotifications($announcement, $validated);
                }

                $this->log('announcement_sent', $announcement, [
                    'title' => $validated['title'],
                    'status' => $status->value,
                    'target_roles' => $validated['target_roles'] ?? 'all',
                ]);

                return $announcement;
            });
        });
    }

    public function publish(Announcement $announcement): void
    {
        $announcement->update([
            'status' => AnnouncementStatus::PUBLISHED,
            'scheduled_at' => null,
        ]);

        $this->sendNotifications($announcement, [
            'title' => $announcement->title,
            'message' => $announcement->message,
            'link' => $announcement->link,
            'target_roles' => $announcement->target_roles,
        ]);

        $this->log('announcement_published', $announcement, [
            'title' => $announcement->title,
            'id' => $announcement->id,
        ]);
    }

    private function sendNotifications(Announcement $announcement, array $config): void
    {
        $users = User::query();

        if (! empty($config['target_roles'])) {
            $senderRoles = auth()->user()->roles->pluck('name')->toArray();

            $users
                ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $senderRoles))
                ->whereHas('roles', fn ($q) => $q->whereIn('name', $config['target_roles']));
        }

        Notification::send(
            $users->get(),
            new AnnouncementNotification(
                title: $announcement->title,
                message: $announcement->message,
                link: $announcement->link,
            ),
        );
    }
}
