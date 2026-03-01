<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Internship\Database\Factories\InternshipPlacementFactory;
use Modules\Log\Concerns\HandlesAuditLog;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Shared\Models\Concerns\HasUuid;

class InternshipPlacement extends Model
{
    use HandlesAuditLog;
    use HasFactory;
    use HasUuid;
    use InteractsWithActivityLog;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'capacity_quota', 'internship_id', 'mentor_id'];

    /**
     * The name of the activity log for this model.
     */
    protected string $activityLogName = 'placement';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InternshipPlacementFactory
    {
        return InternshipPlacementFactory::new();
    }

    /**
     * Get the company (master data) for this placement.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    /**
     * The remaining available slots.
     */
    public function remainingSlots(): int
    {
        return max(
            0,
            $this->capacity_quota -
                $this->registrations()
                    ->whereHas('statuses', function ($query) {
                        $query->where('name', 'active')->whereIn('id', function ($sub) {
                            $sub->selectRaw('max(id)')
                                ->from('statuses')
                                ->whereColumn('model_id', 'internship_registrations.id')
                                ->where('model_type', InternshipRegistration::class);
                        });
                    })
                    ->count(),
        );
    }

    /**
     * The utilization percentage.
     */
    public function utilizationPercentage(): int
    {
        if ($this->capacity_quota === 0) {
            return 0;
        }

        $activeCount = $this->registrations()->whereRelation('statuses', 'name', 'active')->count();

        return (int) min(100, round(($activeCount / $this->capacity_quota) * 100));
    }
}
