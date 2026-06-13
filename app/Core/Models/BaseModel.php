<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasCommonScopes;
    use HasUuids;
}
