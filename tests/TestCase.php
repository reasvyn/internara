<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Academics\Models\Setup;
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
            $setup = Setup::first();
            if ($setup === null) {
                $setup = new Setup;
            }
            $setup->is_installed = true;
            $setup->save();
        } catch (Throwable) {
            // Database table may not exist yet
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
