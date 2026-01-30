<?php

declare(strict_types=1);

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasUuid;

class Competency extends Model
{
    use HasUuid;

    protected $fillable = ['name', 'slug', 'description', 'category'];
}
