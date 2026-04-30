<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatuses;
use Modules\Student\Models\Student;

class AbsenceRequest extends Model
{
    use HasFactory;
    use HasStatuses;
    use HasUuid;
    use InteractsWithActivityLog;

    protected $fillable = ['registration_id', 'student_id', 'date', 'type', 'reason', 'proof_url'];

    /**
     * Relationship with the internship registration.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    /**
     * Relationship with the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
