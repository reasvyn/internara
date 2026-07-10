<?php

declare(strict_types=1);

namespace Tests\Core\Console\Commands;

test('module discover command runs successfully', function () {
    $this->artisan('module:discover')->assertExitCode(0);
});
