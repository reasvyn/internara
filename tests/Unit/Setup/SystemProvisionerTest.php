<?php

declare(strict_types=1);

use App\Domain\Setup\Support\SystemProvisioner;

describe('SystemProvisioner', function () {
    it('returns all provisioning tasks', function () {
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

        expect(fn () => $provisioner->executeTask('nonexistent'))
            ->toThrow(InvalidArgumentException::class);
    });
});
