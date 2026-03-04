<?php

declare(strict_types=1);

describe('Modular Infrastructure Purity Verification', function () {
    test('infrastructure: shared module strict purity', function () {
        expect('Modules\Shared')
            ->toOnlyUse([
                'Modules\Shared',
                'Modules\UI', // Shared provides ManagesModuleProvider which needs SlotRegistry
                'Illuminate',
                'Symfony',
                'Psr',
                'Carbon',
                'Closure',
                'Exception',
                'RuntimeException',
                'Throwable',
                'Modules\Exception',
                'Nwidart\Modules',
            ])
            ->ignoring([
                'app',
                'config',
                'setting',
                'now',
                'today',
                'request',
                'flash',
                'asset',
                '__',
                'is_debug_mode',
                'base_path',
                'module_path',
                'resource_path',
                'storage_path',
                'public_path',
                'database_path',
            ]);
    });

    test('infrastructure: exception module strict purity', function () {
        expect('Modules\Exception')
            ->toOnlyUse([
                'Modules\Exception',
                'Modules\Shared', // For PII Masking
                'Illuminate',
                'Symfony',
                'Symfony\Component\HttpFoundation\Response',
                'Throwable',
                'Exception',
                'RuntimeException',
                'Nwidart\Modules',
            ])
            ->ignoring([
                '__',
                'is_debug_mode',
                'response',
                'redirect',
                'report',
                'flash',
                'module_path',
            ]);
    });

    test('infrastructure: ui core strict purity', function () {
        expect('Modules\UI\Core')
            ->toOnlyUse(['Modules\UI', 'Illuminate', 'Closure', 'Symfony', 'Livewire'])
            ->ignoring(['config', 'collect', 'auth', 'is_debug_mode']);
    });

    test('infrastructure: project genesis requirements', function () {
        expect('Modules\Shared\Support\Result')->classes()->toBeClasses();
    });

    test('infrastructure: ui slot injection requirements', function () {
        expect('Modules\UI\Support\SlotRegistry')->classes()->toBeClasses();
    });

    test('infrastructure: core module baseline', function () {
        expect('Modules\Core')
            ->toOnlyUse([
                'Modules\Shared',
                'Modules\Core',
                'Modules\UI',
                'Illuminate',
                'Spatie',
                'Nwidart',
                'Livewire',
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
    });
});
