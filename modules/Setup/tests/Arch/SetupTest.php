<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Arch;

use Modules\Shared\Services\BaseService;

test('setup module should not depend on concrete domain models')
    ->expect('Modules\Setup')
    ->classes()
    ->not->toUse([
        'Modules\User\Models\User',
        'Modules\School\Models\School',
        'Modules\Department\Models\Department',
        'Modules\Internship\Models\Internship',
        'Modules\Permission\Models\Role',
    ]);

test('installer service should extend BaseService')
    ->expect('Modules\Setup\Services\InstallerService')
    ->classes()
    ->toExtend(BaseService::class);

test('setup logic must reside in services, not livewire components')
    ->expect('Modules\Setup\Livewire')
    ->classes()
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Schema',
        'Illuminate\Support\Facades\Artisan',
    ]);

test('setup protection middleware must remain within setup module')
    ->expect('Modules\Setup\Http\Middleware')
    ->classes()
    ->toBeClasses();
