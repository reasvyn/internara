<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Setup\Components;

use App\Livewire\Setup\Components\WelcomeStep;
use Livewire\Livewire;

test('welcome step renders correctly with audit results', function () {
    $auditResults = [
        'passed' => true,
        'categories' => [
            'environment' => [
                'label' => 'Environment',
                'checks' => [
                    [
                        'name' => 'PHP Version',
                        'status' => 'pass',
                        'message' => 'PHP 8.4 is installed',
                    ],
                ],
            ],
        ],
    ];

    Livewire::test(WelcomeStep::class, [
        'auditResults' => $auditResults,
        'auditPassed' => true,
    ])
        ->assertSee('Environment')
        ->assertSee('PHP Version')
        ->assertSee('Mulai Setup')
        ->assertDontSee('Cek Ulang');
});

test('welcome step shows recheck button when audit fails', function () {
    $auditResults = [
        'passed' => false,
        'categories' => [
            'environment' => [
                'label' => 'Environment',
                'checks' => [
                    [
                        'name' => 'Writable Storage',
                        'status' => 'fail',
                        'message' => 'Storage is not writable',
                    ],
                ],
            ],
        ],
    ];

    Livewire::test(WelcomeStep::class, [
        'auditResults' => $auditResults,
        'auditPassed' => false,
    ])
        ->assertSee('Cek Ulang')
        ->assertDontSee('Mulai Setup');
});

test('welcome step dispatches nextStep event', function () {
    Livewire::test(WelcomeStep::class, [
        'auditResults' => ['categories' => []],
        'auditPassed' => true,
    ])
        ->call('nextStep')
        ->assertDispatched('nextStep');
});

test('welcome step dispatches runAudit event', function () {
    Livewire::test(WelcomeStep::class, [
        'auditResults' => ['categories' => []],
        'auditPassed' => false,
    ])
        ->call('runAudit')
        ->assertDispatched('runAudit');
});
