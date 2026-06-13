<?php

declare(strict_types=1);

namespace App\SysAdmin\Http\Controllers;

use App\User\Models\User;
use App\User\UserManagement\Actions\GenerateAccountSlipAction;
use Illuminate\Http\Request;

final class AccountSlipController
{
    public function download(User $user, GenerateAccountSlipAction $action): mixed
    {
        return $action->download($user);
    }

    public function downloadBatch(Request $request, GenerateAccountSlipAction $action): mixed
    {
        $ids = explode(',', $request->string('ids', ''));
        $users = User::whereIn('id', $ids)->get();

        return $action->downloadBatch($users->all());
    }
}
