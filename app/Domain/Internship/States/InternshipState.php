<?php

declare(strict_types=1);

namespace App\Domain\Internship\States;

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\States\BaseState;
use App\Domain\Internship\Enums\InternshipStatus;
use Spatie\ModelStates\StateConfig;

abstract class InternshipState extends BaseState
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Published::class)
            ->allowTransition(Draft::class, Cancelled::class)
            ->allowTransition(Published::class, Active::class)
            ->allowTransition(Published::class, Cancelled::class)
            ->allowTransition(Active::class, Completed::class)
            ->allowTransition(Active::class, Cancelled::class);
    }

    public function toEnum(): ?LabelEnum
    {
        return InternshipStatus::tryFrom($this->getValue());
    }
}
