<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract 
/**
 * @property string $id
 */

class BaseAuthenticatable extends Authenticatable
{
    use HasCommonScopes;
    use HasUuids;
}
