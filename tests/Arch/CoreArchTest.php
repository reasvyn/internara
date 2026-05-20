<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Core\Support\SmartLogger;

arch('Core/Settings contracts are interfaces')
    ->expect(LabelEnum::class)
    ->toBeInterface()
    ->and(StatusEnum::class)
    ->toBeInterface();

arch('Core Data classes are readonly')
    ->expect(AuditReport::class)
    ->toBeReadonly()
    ->and(AuditCheck::class)
    ->toBeReadonly();

arch('Core entities are readonly')
    ->expect(BaseEntity::class)
    ->toBeReadonly();

arch('Core actions do not depend on Livewire')
    ->expect(SmartLogger::class)
    ->not->toUse('Livewire');
