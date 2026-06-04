<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Account\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Enums\Role;
use Illuminate\Database\Eloquent\Builder;

final class ArchiveStudentAccountsAction extends BaseAction
{
    public function execute(Builder $query): int
    {
        $count = 0;

        $query->chunk(100, function ($users) use (&$count) {
            foreach ($users as $user) {
                if ($user->hasRole(Role::SUPER_ADMIN->value)) {
                    continue;
                }

                $user->setStatus('archived', 'Mass archived via Student Manager');
                $count++;
            }
        });

        return $count;
    }
}
