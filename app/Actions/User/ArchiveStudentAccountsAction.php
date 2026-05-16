<?php

declare(strict_types=1);

namespace App\Actions\User;

use Illuminate\Database\Eloquent\Builder;

class ArchiveStudentAccountsAction
{
    public function execute(Builder $query): int
    {
        $count = 0;

        $query->chunk(100, function ($users) use (&$count) {
            foreach ($users as $user) {
                $user->setStatus('archived', 'Mass archived via Student Manager');
                $count++;
            }
        });

        return $count;
    }
}
