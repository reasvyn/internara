<?php

declare(strict_types=1);

use Modules\Core\Metadata\Services\Contracts\MetadataService as MetadataContract;
use Modules\Core\Metadata\Services\MetadataService;

describe('Infrastructure: BindServiceProvider Discovery', function () {
    test('it automatically binds nested service contracts to their implementations', function () {
        // Verify that MetadataService is correctly bound via the zero-config DI
        $instance = app(MetadataContract::class);

        expect($instance)->toBeInstanceOf(MetadataService::class);
    });

    test(
        'it follows the folder signature src/Services/Contracts for automatic mapping',
        function () {
            // This test ensures that even if a service is in a nested domain, it gets bound
            $bindings = app()->getBindings();

            expect(isset($bindings[MetadataContract::class]))->toBeTrue();
        },
    );

    test('it distinguishes between product identity and instance identity', function () {
        $service = app(MetadataContract::class);

        // App name should match app_info.json name
        expect($service->getAppName())->toBe('Internara');

        // Brand name defaults to app name if not set
        expect($service->getBrandName())->toBe($service->getAppName());
    });
});
