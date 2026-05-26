<?php

declare(strict_types=1);

namespace App\Domain\Core\States;

use App\Domain\Core\Entities\BaseEntity;

/**
 * Base class for state entities.
 *
 * State entities represent the current state of a business process
 * (e.g., setup state, registration state). They are immutable value
 * objects derived from Eloquent models via fromModel().
 *
 * Unlike BaseEntity, BaseState provides state-machine helpers:
 * status checks, transition validation, and window-based expiry.
 */
abstract readonly class BaseState extends BaseEntity
{
    public function isState(string $state): bool
    {
        return property_exists($this, 'status') && $this->status === $state;
    }

    public function isStateIn(array $states): bool
    {
        return property_exists($this, 'status') && in_array($this->status, $states, true);
    }
}
