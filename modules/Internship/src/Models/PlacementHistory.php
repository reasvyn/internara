<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Models\Concerns\HasUuid;

/**
 * Represents a log entry in the placement lifecycle.
 *
 * This model tracks when a student is assigned to a placement,
 * when it changes, or when it is marked as completed.
 */
class PlacementHistory extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'internship_placement_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['registration_id', 'placement_id', 'action', 'reason', 'metadata'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the registration associated with this history record.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    /**
     * Get the placement associated with this history record.
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(InternshipPlacement::class, 'placement_id');
    }
}
