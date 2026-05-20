<?php

declare(strict_types=1);

namespace App\Domain\Core\Contracts;

/**
 * Contract for all status enums across domains.
 *
 * Provides lifecycle methods that every status enum must implement:
 * - Terminal state detection (isTerminal)
 * - Transition validation (canTransitionTo, validTransitions)
 * - Human-readable label (from LabelEnum)
 */
interface StatusEnum extends LabelEnum
{
    /**
     * Whether this status is a terminal (final) state with no further transitions.
     */
    public function isTerminal(): bool;

    /**
     * Whether a transition to the given target status of the same enum is allowed.
     */
    public function canTransitionTo(self $target): bool;

    /**
     * List of valid target statuses that can be transitioned to from this state.
     *
     * @return list<static>
     */
    public function validTransitions(): array;
}
