<?php

declare(strict_types=1);

namespace Modules\Permission\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Permission\Models\Permission
 *
 * @extends EloquentQuery<TModel>
 */
interface PermissionService extends EloquentQuery
{
    //
}
