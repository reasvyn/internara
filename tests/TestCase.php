<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\WithSettingsSeed;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use WithSettingsSeed;
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->seedSettings([
                'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
            ]);
        } catch (Throwable) {
            // Database table may not exist yet
        }

        try {
            Role::findOrCreate('superadmin', 'web');
            Role::findOrCreate('admin', 'web');
            Role::findOrCreate('student', 'web');
            Role::findOrCreate('teacher', 'web');
            Role::findOrCreate('supervisor', 'web');
        } catch (Throwable) {
            // Roles table may not exist yet
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });
    }
}
