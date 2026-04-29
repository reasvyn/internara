<?php

declare(strict_types=1);

namespace Tests\Arch;

/**
 * S1 - Secure & S2 - Sustain: Universal Coding Standards
 */
test('global: strict types')
    ->expect('App')
    ->toUseStrictTypes();

test('global: clean code invariants')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'die', 'var_dump', 'env'])
    ->ignoring([
        // Add specific exceptions here if needed
    ]);

/**
 * S1 - Secure & S3 - Scalable: UUID Enforcement
 * All models must use UUIDs for primary keys.
 */
test('models should use HasUuid trait')
    ->expect('App\Models')
    ->classes()
    ->toUseTrait('App\Models\Concerns\HasUuid')
    ->ignoring([
        'App\Models\Concerns', // Ignore the trait itself
    ]);

/**
 * S2 - Sustain: Logic Clarity and Separation
 * Ensures that controllers are thin and only act as entry points.
 */
test('controllers should not directly use models for data manipulation')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models')
    ->ignoring(['App\Http\Controllers\Controller']);

/**
 * S1 - Secure & S3 - Scalable: Stateless Actions
 * Actions must be stateless to ensure they can be safely reused across entry points.
 */
test('actions should be stateless', function () {
    expect('App\Actions')->classes();
})->todo();

/**
 * S2 - Sustain: Use Case Encapsulation
 * Every action should follow the single action pattern with an execute method.
 */
test('actions should have an execute method')
    ->expect('App\Actions')
    ->toHaveMethod('execute');

/**
 * S1 - Secure: Protected Rules
 * Business rules should be encapsulated in Models, not leaked into Actions or Controllers.
 */
test('models should not depend on actions')
    ->expect('App\Models')
    ->not->toUse('App\Actions');

/**
 * Domain Grouping
 * Ensure everything is correctly placed in the root App namespace during/after migration.
 */
test('app namespace should be organized by layer')
    ->expect('App')
    ->toOnlyUse([
        'App\Actions',
        'App\Models',
        'App\Http',
        'App\Providers',
        'App\Enums',
        'App\Data',
        'App\Exceptions',
        'App\Services',
        'App\Livewire',
        'App\Support',
        'Illuminate',
        'Spatie',
        'Carbon',
        'Symfony',
        'Mary',
        'Livewire',
    ])->ignoring(['__', 'abort', 'str', 'auth', 'app', 'now', 'config', 'collect']);
