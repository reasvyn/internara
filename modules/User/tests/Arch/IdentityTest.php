<?php

declare(strict_types=1);

namespace Modules\User\Tests\Arch;

test('identity models should use UUID trait')
    ->expect(['Modules\User\Models\User', 'Modules\Profile\Models\Profile'])
    ->toUse('Modules\Shared\Models\Concerns\HasUuid');

test('identity anchors should be isolated')
    ->expect('Modules\User\Models\User')
    ->toOnlyBeUsedIn([
        'Modules\Auth',
        'Modules\User',
        'Modules\Profile',
        'Modules\Permission',
        'Modules\Teacher',
        'Modules\Student',
        'Modules\Mentor',
        'Modules\Admin',
        'Modules\Internship',
        'Modules\Assessment',
        'Modules\Attendance',
        'Modules\Journal',
        'Modules\Assignment',
        'Modules\Schedule',
        'Modules\Guidance',
        'Modules\Report',
        'Modules\Shared',
        'Modules\Core',
        'Modules\Setup',
        'Modules\Log',
        'Modules\Notification',
        'Modules\Media',
    ]);

test('profile models should be isolated')
    ->expect('Modules\Profile\Models\Profile')
    ->toOnlyBeUsedIn(['Modules\User', 'Modules\Profile', 'Modules\Shared']);
