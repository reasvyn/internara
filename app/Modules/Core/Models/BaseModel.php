<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasCommonScopes;
    use HasUuids;
}
