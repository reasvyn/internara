<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Actions\Core\LogAuditAction;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use App\Support\User\HandlesActionErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SendAnnouncementAction
{
    use HandlesActionErrors;

    public function __construct(
        protected readonly LogAuditAction $logAudit,
    ) {}

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
            return DB::transaction(function () use ($validated) {
                $announcement = Announcement::create([
                    'title' => $validated['title'],
                    'message' => $validated['message'],
                    'type' => $validated['type'],
                    'link' => $validated['link'] ?? null,
                    'target_roles' => $validated['target_roles'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                $senderRoles = auth()->user()->roles->pluck('name')->toArray();

                $users = User::query()
                    ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $senderRoles));

                if (! empty($validated['target_roles'])) {
                    $users->whereHas('roles', fn ($q) => $q->whereIn('name', $validated['target_roles']));
                }

                Notification::send($users->get(), new AnnouncementNotification(
                    title: $validated['title'],
                    message: $validated['message'],
                    link: $validated['link'] ?? null,
                ));

                $this->logAudit->execute(
                    action: 'announcement_sent',
                    subjectType: Announcement::class,
                    subjectId: $announcement->id,
                    payload: [
                        'title' => $validated['title'],
                        'target_roles' => $validated['target_roles'] ?? 'all',
                    ],
                    module: 'Notification',
                );

                return $announcement;
            });
        }, 'Failed to send announcement');
    }
}
