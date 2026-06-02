<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Support;

use App\Domain\Setup\Support\SystemProvisioner;

describe('SystemProvisioner', function () {
    it('returns list of tasks', function () {
        $provisioner = new SystemProvisioner;
        $tasks = $provisioner->getTasks();

        expect($tasks)->toHaveKeys([
            'ensure_env',
            'generate_key',
            'run_migrations',
            'run_seeders',
            'storage_link',
            'clear_cache',
        ]);
    });

    it('throws for unknown task', function () {
        $provisioner = new SystemProvisioner;

        $provisioner->executeTask('unknown_task');
    })->throws(\InvalidArgumentException::class);
});
