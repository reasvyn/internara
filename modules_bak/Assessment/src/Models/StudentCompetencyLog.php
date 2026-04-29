<?php

declare(strict_types=1);

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasUuid;

class StudentCompetencyLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'registration_id',
        'competency_id',
        'score',
        'notes',
        'proof_url',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function competency()
    {
        return $this->belongsTo(Competency::class);
    }
}
