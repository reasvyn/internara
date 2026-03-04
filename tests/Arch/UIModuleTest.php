<?php

declare(strict_types=1);

/**
 * UI Architecture Invariants Verification.
 */
describe('UI: Architecture Invariants', function () {
    test('it does not depend on specific domain models in RecordManager', function () {
        expect('Modules\UI\Livewire\RecordManager')
            ->classes()
            ->not->toUse('Modules\User\Models\User')
            ->not->toUse('Modules\Exception');
    });

    test('it uses the consolidated SlotRegistry from Core', function () {
        expect('Modules\UI\Support\SlotRegistry')->not->toBeClasses();

        expect('Modules\UI\Facades\SlotRegistry')
            ->classes()
            ->toExtend('Illuminate\Support\Facades\Facade');
    });

    arch('ui: strict presentation purity')
        ->expect('Modules\UI')
        ->classes()
        ->not->toUse([
            'Modules\Assignment',
            'Modules\Attendance',
            'Modules\Internship',
            'Modules\Journal',
            'Modules\Assessment',
        ]);
});
