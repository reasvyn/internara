<?php

declare(strict_types=1);

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Academic\Models\Concerns\HasAcademicYear;
use Modules\Schedule\Database\Factories\ScheduleFactory;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Shared\Models\Concerns\HasUuid;

class Schedule extends Model
{
    use HasAcademicYear;
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'title',
        'description',
        'start_at',
        'end_at',
        'type',
        'location',
        'internship_id',
        'academic_year',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'type' => ScheduleType::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ScheduleFactory
    {
        return ScheduleFactory::new();
    }
}
