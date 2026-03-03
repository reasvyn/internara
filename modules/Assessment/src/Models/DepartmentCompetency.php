<?php

declare(strict_types=1);

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasUuid;

class DepartmentCompetency extends Model
{
    use HasUuid;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    protected $fillable = ['id', 'department_id', 'competency_id', 'weight'];

    public function competency()
    {
        return $this->belongsTo(Competency::class);
    }
}
