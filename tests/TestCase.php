<?php

declare(strict_types=1);

namespace Tests;

use App\SysAdmin\Settings\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Gate;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            Settings::set([
                'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
            ]);
        } catch (Throwable) {
            // Database table may not exist yet
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
