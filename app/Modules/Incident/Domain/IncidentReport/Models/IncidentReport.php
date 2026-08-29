<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain\IncidentReport\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentSeverity;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentStatus;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentType;
use App\Modules\User\Models\User;
use Database\Factories\IncidentReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
