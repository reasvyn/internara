<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\Internship\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;

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
