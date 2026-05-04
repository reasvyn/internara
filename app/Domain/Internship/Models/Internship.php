<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Concerns\HasUuid;
use App\Domain\Internship\Enums\InternshipStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'start_date', 'end_date', 'description', 'status'])]
class Internship extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => InternshipStatus::class,
    ];

    public function placements()
    {
        return $this->hasMany(Placement::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function isAcceptingRegistrations(): bool
    {
        return $this->status?->isAcceptingRegistrations() ?? false;
    }
}
