<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Incident\IncidentSeverity;
use App\Enums\Incident\IncidentStatus;
use App\Enums\Incident\IncidentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'registration_id', 'reported_by', 'incident_date', 'type', 'severity',
    'description', 'location', 'action_taken', 'status',
    'resolved_by', 'resolved_at', 'resolution_notes',
])]
class IncidentReport extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => 'reported',
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
}
