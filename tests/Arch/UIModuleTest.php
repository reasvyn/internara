<?php

declare(strict_types=1);

describe('UI: Architecture Invariants', function () {
    test('it does not depend on specific domain models in RecordManager', function () {
        expect('Modules\UI\Livewire\RecordManager')
            ->not->toUse('Modules\User\Models\User')
            ->not->toUse('Modules\Exception');
    });

    test('it uses the consolidated SlotRegistry from Core', function () {
        expect('Modules\UI\Support\SlotRegistry')
            ->not->toBeClasses();
        
        expect('Modules\UI\Facades\SlotRegistry')
            ->toExtend('Illuminate\Support\Facades\Facade');
    });
});
