<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\User\Models\User;

final class BatchDeleteUserAction extends BaseCommandAction
{
    public function __construct(protected readonly DeleteUserAction $deleteAction) {}

    /**
     * @param string[] $ids
     *
     * @return array{deleted: int, skipped: int}
     */
    public function execute(array $ids): array
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            if ($id === auth()->id()) {
                $skipped++;

                continue;
            }

            $user = User::find($id);

            if (! $user || $user->hasRole('super_admin')) {
                $skipped++;

                continue;
            }

            try {
                $this->deleteAction->execute($user);
                $deleted++;
            } catch (\RuntimeException) {
                $skipped++;
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }
}
