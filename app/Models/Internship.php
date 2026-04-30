<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InternshipStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'description',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => InternshipStatus::class,
    ];

    public function placements()
    {
        return $this->hasMany(InternshipPlacement::class);
    }

    public function registrations()
    {
        return $this->hasMany(InternshipRegistration::class);
    }

    public function isAcceptingRegistrations(): bool
    {
        return $this->status?->isAcceptingRegistrations() ?? false;
    }
}
