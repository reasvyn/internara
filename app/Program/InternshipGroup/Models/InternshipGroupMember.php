<?php

declare(strict_types=1);

namespace App\Program\InternshipGroup\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Models\Registration;
use App\Guidance\Mentor\Models\Mentor;
use Database\Factories\InternshipGroupMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['internship_group_id', 'registration_id', 'mentor_id', 'role', 'joined_at'])]
class InternshipGroupMember extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): InternshipGroupMemberFactory
    {
        return InternshipGroupMemberFactory::new();
    }

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
