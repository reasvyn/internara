<?php

declare(strict_types=1);

namespace Modules\Admin\Services\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<Authenticatable&Model>
 */
interface AdminService extends EloquentQuery
{
    /**
     * Send (or resend) an invitation email to an Admin account.
     * Only callable while the account is unclaimed (setup_required = true).
     */
    public function invite(
        Authenticatable&Model $admin,
        (Authenticatable&Model)|null $issuedBy = null,
        int $expiresInDays = 7
    ): void;
}
