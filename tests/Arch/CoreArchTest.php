<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\ColorableEnum;
use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\SendsNotifications;
use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Data\Data;
use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Core\Support\Integrity;
use App\Domain\Core\Support\PiiMasker;
use App\Domain\Core\Support\SmartLogger;

arch('Core contracts are interfaces')
    ->expect(LabelEnum::class)
    ->toBeInterface()
    ->and(StatusEnum::class)
    ->toBeInterface()
    ->and(ColorableEnum::class)
    ->toBeInterface()
    ->and(SendsNotifications::class)
    ->toBeInterface();

arch('Core Data classes are readonly')
    ->expect(AuditReport::class)
    ->toBeReadonly()
    ->and(AuditCheck::class)
    ->toBeReadonly();

arch('Core Data base class is abstract readonly')
    ->expect(Data::class)
    ->toBeAbstract()
    ->toBeReadonly();

arch('Core entities are readonly')
    ->expect(BaseEntity::class)
    ->toBeReadonly();

arch('Core support classes are final')
    ->expect(SmartLogger::class)
    ->toBeFinal()
    ->and(PiiMasker::class)
    ->toBeFinal()
    ->and(Integrity::class)
    ->toBeFinal();

arch('Core support does not depend on Livewire')
    ->expect(SmartLogger::class)
    ->not->toUse('Livewire')
    ->and(Integrity::class)
    ->not->toUse('Livewire');

arch('Core support does not import business domains')
    ->expect(SmartLogger::class)
    ->not->toUse('App\Domain\Admin')
    ->not->toUse('App\Domain\Auth')
    ->not->toUse('App\Domain\User')
    ->and(PiiMasker::class)
    ->not->toUse('App\Domain\Admin')
    ->not->toUse('App\Domain\Auth')
    ->not->toUse('App\Domain\User')
    ->and(Integrity::class)
    ->not->toUse('App\Domain\Admin')
    ->not->toUse('App\Domain\Auth')
    ->not->toUse('App\Domain\User');
