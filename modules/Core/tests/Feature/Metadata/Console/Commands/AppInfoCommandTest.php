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
