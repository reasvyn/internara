<?php

declare(strict_types=1);

namespace App\Jobs\User;

use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\UserManagement\Actions\SetUserStatusAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ArchiveStudentAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [2, 10, 30];

    public function __construct(
        protected readonly array $studentIds,
    ) {}

    public function handle(SetUserStatusAction $setUserStatus): void
    {
        $users = User::query()
            ->whereIn('id', $this->studentIds)
            ->get();

        foreach ($users as $user) {
            $setUserStatus->execute(
                $user,
                AccountStatus::ARCHIVED,
                reason: __('user.manager.status_archived_bulk'),
                skipAuthCheck: true,
            );
        }
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('Batch student archiving failed', [
            'student_ids' => $this->studentIds,
            'error' => $e->getMessage(),
        ]);
    }
}
