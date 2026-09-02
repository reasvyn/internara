<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain\IncidentReport\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentSeverity;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentStatus;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentType;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\IncidentReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property Carbon $incident_date
 * @property Carbon $resolved_at
 * @property IncidentType $type
 * @property IncidentSeverity $severity
 * @property IncidentStatus $status
 * @property string $registration_id
 * @property string $reported_by
 * @property string $description
 * @property string $location
 * @property string $action_taken
 * @property string $resolved_by
 * @property string $resolution_notes
 * @property-read Registration|null $registration
 * @property-read User|null $reporter
 * @property-read User|null $resolver
 */
#[
    Fillable([
        'registration_id',
        'reported_by',
        'incident_date',
        'type',
        'severity',
        'description',
        'location',
        'action_taken',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ]),
]
class IncidentReport extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => IncidentStatus::REPORTED->value,
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'datetime',
            'resolved_at' => 'datetime',
            'type' => IncidentType::class,
            'severity' => IncidentSeverity::class,
            'status' => IncidentStatus::class,
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    protected static function newFactory(): IncidentReportFactory
    {
        return IncidentReportFactory::new();
    }
}
