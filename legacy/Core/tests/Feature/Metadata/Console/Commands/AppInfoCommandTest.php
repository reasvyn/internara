<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Metadata\Console\Commands;

use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

test('it displays correct metadata from app_info.json', function () {
    $path = base_path('app_info.json');
    $info = json_decode(File::get($path), true);

    artisan('app:info')
        ->expectsOutputToContain('Internara Application Information')
        ->expectsOutputToContain($info['version'])
        ->expectsOutputToContain($info['author']['name'])
        ->assertExitCode(0);
});

test('it handles missing composer.json gracefully', function () {
    $path = base_path('composer.json');
    $original = File::exists($path) ? File::get($path) : null;

    if ($original) {
        File::delete($path);
    }

    artisan('app:info')
        ->assertExitCode(0); // Should not crash

    if ($original) {
        File::put($path, $original);
    }
});

test('it handles missing package.json gracefully', function () {
    $path = base_path('package.json');
    $original = File::exists($path) ? File::get($path) : null;

    if ($original) {
        File::delete($path);
    }

    artisan('app:info')
        ->assertExitCode(0); // Should not crash

    if ($original) {
        File::put($path, $original);
    }
});
