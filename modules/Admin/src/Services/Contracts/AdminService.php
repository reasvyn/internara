<?php

declare(strict_types=1);

namespace Modules\Admin\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;
use Modules\User\Models\User;

/**
 * @extends EloquentQuery<User>
 */
interface AdminService extends EloquentQuery
{
    /**
     * Send (or resend) an invitation email to an Admin account.
     * Only callable while the account is unclaimed (setup_required = true).
     */
    public function invite(User $admin, ?User $issuedBy = null, int $expiresInDays = 7): void;
}
