<?php

declare(strict_types=1);

namespace App\Domain\Core\States;

use App\Domain\Core\Contracts\LabelEnum;
use Spatie\ModelStates\State;

/**
 * Base class for all domain state machines.
 *
 * Provides standard methods that map to the StatusEnum contract
 * so every state class can be used with label(), isTerminal(), etc.
 */
abstract class BaseState extends State
{
    /**
     * Human-readable label for this state.
     * Override in concrete state classes.
     */
    public function label(): string
    {
        return (new \ReflectionClass(static::class))->getShortName();
    }

    /**
     * Whether this state is terminal (no further transitions allowed).
     * Override in concrete state classes if needed.
     */
    public function isTerminal(): bool
    {
        return false;
    }

    /**
     * Resolve a LabelEnum value for badge/display purposes.
     * State classes that map to a StatusEnum can override this.
     */
    public function toEnum(): ?LabelEnum
    {
        return null;
    }
}
