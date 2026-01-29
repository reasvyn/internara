<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\Concerns\HandlesAuditLog;
use Modules\Internship\Database\Factories\InternshipPlacementFactory;
use Modules\Shared\Models\Concerns\HasUuid;

class InternshipPlacement extends Model
{
    use HandlesAuditLog;
    use HasFactory;
    use HasUuid;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_name',
        'company_address',
        'contact_person',
        'contact_number',
        'capacity_quota',
        'internship_id',
        'mentor_id',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InternshipPlacementFactory
    {
        return InternshipPlacementFactory::new();
    }

    /**
     * Get the internship program that owns the placement.
     */
    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    /**
     * Get the mentor (user) associated with the placement.
     */
    public function mentor(): BelongsTo
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo(
            $this,
            'mentor_id',
        );
    }

    /**
     * Get the registrations for this placement.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(InternshipRegistration::class, 'placement_id');
    }
}
