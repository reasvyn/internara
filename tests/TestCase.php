<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        $app = $this->app;

        parent::tearDown();

        if ($app) {
            $app->flush();
        }

        gc_collect_cycles();
    }
}
