<?php

declare(strict_types=1);

/**
 * Modular Infrastructure Purity Verification.
 */

arch('infrastructure: shared module isolation')
    ->expect('Modules\Shared')
    ->not->toUse('Modules')
    ->ignoring(['Modules\Shared', 'Nwidart\Modules']);

arch('infrastructure: project genesis requirements')
    ->expect('Modules\Shared\Support\Result')
    ->toBeClasses();

arch('infrastructure: exception resilience requirements')
    ->expect('Modules\Exception\RecordNotFoundException')
    ->toBeClasses();

arch('infrastructure: ui slot injection requirements')
    ->expect('Modules\UI\Support\SlotRegistry')
    ->toBeClasses();

arch('infrastructure: core module baseline')
    ->expect('Modules\Core')
    ->toOnlyUse([
        'Modules\Shared',
        'Modules\Core',
        'Modules\UI',
        'Illuminate',
        'Spatie',
        'Nwidart',
        'Livewire',
        'Livewire\Volt',
        'Symfony',
        'Psr',
        'Composer',
    ])
    ->ignoring([
        'app',
        'config',
        'setting',
        'url',
        'redirect',
        'request',
        'session',
        'trans',
        '__',
        'view',
        'Symfony\Component\HttpFoundation',
        'is_debug_mode',
        'base_path',
        'now',
        'module_path',
        'flash',
    ]);
