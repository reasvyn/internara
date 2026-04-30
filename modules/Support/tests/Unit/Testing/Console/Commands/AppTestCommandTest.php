<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Unit\Testing\Console\Commands;

use Illuminate\Support\Facades\Config;

describe('AppTestCommand', function () {
    beforeEach(function () {
        Config::set('app.env', 'testing');
        Config::set('app.version', '0.14.0');
    });

    it('displays the modular verification banner with module info', function () {
        $this->artisan('app:test --list')->expectsOutputToContain('INTERNARA')->assertExitCode(0);
    });

    it('lists available test segments', function () {
        $this->artisan('app:test --list')->assertExitCode(0);
    });

    it('can filter to specific modules in list mode', function () {
        $this->artisan('app:test Shared --list')->assertExitCode(0);
    });
});
