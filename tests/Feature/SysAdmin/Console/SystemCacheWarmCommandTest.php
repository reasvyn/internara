<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Console;

use Illuminate\Support\Facades\File;

afterEach(function () {
    @unlink(base_path('bootstrap/cache/config.php'));
    @unlink(base_path('bootstrap/cache/events.php'));
});

test('system:cache-warm runs successfully and creates cache files', function () {
    $this->artisan('system:cache-warm')
        ->expectsOutputToContain(__('setup.system.cache_warm_starting'))
        ->expectsOutputToContain(__('setup.system.cache_warm_completed'))
        ->assertSuccessful();

    expect(File::exists(base_path('bootstrap/cache/config.php')))->toBeTrue();
});
