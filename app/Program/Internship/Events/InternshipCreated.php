<?php

declare(strict_types=1);

namespace App\Program\Internship\Events;

use App\Core\Events\BaseEvent;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;

final class InternshipCreated extends BaseEvent
{
    public function __construct(
        public Internship $internship,
        public ?User $createdBy = null,
    ) {}

    public function eventName(): string
    {
        return 'internship.created';
    }
}
