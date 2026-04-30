<?php

declare(strict_types=1);

namespace Modules\Permission\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Permission\Services\Contracts\PermissionManager as PermissionManagerContract;

class PermissionManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PermissionManagerContract::class;
    }
}
