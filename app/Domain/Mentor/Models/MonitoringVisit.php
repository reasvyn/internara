<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'teacher_id', 'date', 'notes', 'company_feedback', 'student_condition', 'attachment_path', 'status'])]
class MonitoringVisit extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'date' => 'date',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
