<?php

declare(strict_types=1);

namespace Modules\User\Tests\Arch;

test('identity models should use UUID trait')
    ->expect(['Modules\User\Models\User', 'Modules\Profile\Models\Profile'])
    ->toUse('Modules\Shared\Models\Concerns\HasUuid');

test('identity anchors should be isolated')
    ->expect('Modules\User\Models\User')
    ->not->toBeUsedOutside([
        'Modules\Auth',
        'Modules\User',
        'Modules\Profile',
        'Modules\Permission',
        'Modules\Shared', // For base abstractions
        'Modules\Core', // For global metadata
    ]);

test('profile models should be isolated')
    ->expect('Modules\Profile\Models\Profile')
    ->not->toBeUsedOutside(['Modules\User', 'Modules\Profile', 'Modules\Shared']);
