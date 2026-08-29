<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Auth\Domain\Permissions\Enums\Role;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Builder;

final class ArchiveStudentAccountsAction extends BaseCommandAction
{
    public function execute(Builder $query): int
    {
        $count = 0;

        $query->chunk(100, function ($users) use (&$count) {
            foreach ($users as $user) {
                if ($user->hasRole(Role::SUPER_ADMIN->value)) {
                    continue;
                }

                $user->setStatus(AccountStatus::ARCHIVED->value, 'Mass archived via Student Manager');
                $count++;
            }
        });

        $this->log('student_accounts_archived', null, ['count' => $count]);

        return $count;
    }
}
