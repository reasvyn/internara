<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\Registration\Models\Registration;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['internship_group_id', 'registration_id', 'mentor_id', 'role', 'joined_at'])]
class InternshipGroupMember extends BaseModel
{
    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(InternshipGroup::class, 'internship_group_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }
}
