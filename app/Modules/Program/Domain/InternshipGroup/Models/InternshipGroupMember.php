<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\InternshipGroupMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property Carbon $joined_at
 * @property string $internship_group_id
 * @property string $registration_id
 * @property string $user_id
 * @property string $role
 * @property-read InternshipGroup|null $group
 * @property-read Registration|null $registration
 * @property-read User|null $user
 */
#[Fillable(['internship_group_id', 'registration_id', 'user_id', 'role', 'joined_at'])]
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
