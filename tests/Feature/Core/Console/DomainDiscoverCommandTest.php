<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

test('domain:discover command runs successfully', function () {
    $this->artisan('domain:discover')
        ->assertExitCode(0);
});
