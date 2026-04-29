<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStatus\HasStatuses;

/**
 * Represents a student's registration for a specific internship program.
 */
class InternshipRegistration extends Model
{
    use HasFactory, HasUuid, HasStatuses;

    protected $fillable = [
        'student_id',
        'internship_id',
        'placement_id',
        'teacher_id',
        'mentor_id',
        'academic_year',
        'start_date',
        'end_date',
        'proposed_company_name',
        'proposed_company_address',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(InternshipPlacement::class, 'placement_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }
}
