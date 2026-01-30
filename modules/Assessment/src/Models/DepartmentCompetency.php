<?php

declare(strict_types=1);

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasUuid;

class DepartmentCompetency extends Model
{
    use HasUuid;

    protected $fillable = ['department_id', 'competency_id', 'weight'];

    public function competency()
    {
        return $this->belongsTo(Competency::class);
    }
}
