<?php

declare(strict_types=1);

namespace Tests\Arch\OptionalLayers;

/**
 * S3 - Scalable: Repository Standards
 * Ensures Repositories are used correctly (only when needed)
 */
describe('Repository Standards', function () {

    test('repositories should use strict types')
        ->expect('App\Repositories')
        ->toUseStrictTypes();

    test('repositories should not contain business logic')
        ->expect('App\Repositories')
        ->not->toUse([
            'event(', 'dispatch(',
            'notification', 'Notification::',
        ]);

    test('repositories should only do reads (no writes)')
        ->expect('App\Repositories')
        ->not->toUse([
            '->create(', '->update(', '->delete(', '->save(',
            'insert', 'update', 'delete', 'DB::insert', 'DB::update', 'DB::delete',
        ]);
});
