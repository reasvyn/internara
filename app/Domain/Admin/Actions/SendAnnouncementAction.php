<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Admin\Models\Announcement;
use App\Domain\Admin\Notifications\AnnouncementNotification;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SendAnnouncementAction extends BaseAction
{
    public function execute(array $data): Announcement
    {
        $validated = Validator::validate($data, [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'required|in:info,success,warning,error',
            'link' => 'nullable|string|max:500',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string|exists:roles,name',
        ]);

        return $this->withErrorHandling(function () use ($validated) {
            return $this->transaction(function () use ($validated) {
                $announcement = Announcement::create([
                    'title' => $validated['title'],
                    'message' => $validated['message'],
                    'type' => $validated['type'],
                    'link' => $validated['link'] ?? null,
                    'target_roles' => $validated['target_roles'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                $users = User::query();

                if (! empty($validated['target_roles'])) {
                    $senderRoles = auth()->user()->roles->pluck('name')->toArray();

                    $users
                        ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $senderRoles))
                        ->whereHas('roles', fn ($q) => $q->whereIn('name', $validated['target_roles']));
                }

                Notification::send($users->get(), new AnnouncementNotification(
                    title: $validated['title'],
                    message: $validated['message'],
                    link: $validated['link'] ?? null,
                ));

                $this->log('announcement_sent', $announcement, [
                    'title' => $validated['title'],
                    'target_roles' => $validated['target_roles'] ?? 'all',
                ]);

                return $announcement;
            });
        }, 'Failed to send announcement');
    }
}
