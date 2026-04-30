<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringVisit extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'registration_id',
        'teacher_id',
        'date',
        'notes',
        'company_feedback',
        'student_condition',
        'attachment_path',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
