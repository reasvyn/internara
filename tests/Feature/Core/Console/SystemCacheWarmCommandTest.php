<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

use Illuminate\Support\Facades\File;

afterEach(function () {
    // Clean up cached files written during warming to avoid affecting other tests
    @unlink(base_path('bootstrap/cache/config.php'));
    @unlink(base_path('bootstrap/cache/events.php'));
});

test('system:cache-warm command runs successfully under normal circumstances', function () {
    $this->artisan('system:cache-warm')
        ->expectsOutputToContain(__('setup.system.cache_warm_starting'))
        ->expectsOutputToContain(__('setup.system.cache_warm_completed'))
        ->assertSuccessful();

    // Verify cache files were created
    expect(File::exists(base_path('bootstrap/cache/config.php')))->toBeTrue();
});
