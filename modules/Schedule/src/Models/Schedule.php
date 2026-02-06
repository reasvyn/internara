<?php

declare(strict_types=1);

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasAcademicYear;
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
        'type' => \Modules\Schedule\Enums\ScheduleType::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Schedule\Database\Factories\ScheduleFactory
    {
        return \Modules\Schedule\Database\Factories\ScheduleFactory::new();
    }
}
