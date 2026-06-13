<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class BaseAuthenticatable extends Authenticatable
{
    use HasCommonScopes;
    use HasUuids;
}
