<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Internship;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event fired when a new internship is created.
 * 
 * S3 - Scalable: Decouples side effects from core business logic.
 * Use when multiple things need to happen after an internship is created.
 */
class InternshipCreated
{
    use Dispatchable;
    
    public function __construct(
        public readonly Internship $internship,
        public readonly User $createdBy,
    ) {}
}
