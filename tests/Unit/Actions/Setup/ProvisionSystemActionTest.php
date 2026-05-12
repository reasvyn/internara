<?php

declare(strict_types=1);

use App\Support\Setup\SystemProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('getTasks', function () {
    it('returns list of provisioning tasks', function () {
        $tasks = app(SystemProvisioner::class)->getTasks();

        expect($tasks)->toHaveKeys(['ensure_env', 'generate_key', 'run_migrations', 'run_seeders', 'storage_link', 'clear_cache']);
    });
});

describe('executeTask', function () {
    it('throws InvalidArgumentException for unknown task', function () {
        expect(fn () => app(SystemProvisioner::class)->executeTask('invalid_task'))
            ->toThrow(InvalidArgumentException::class);
    });
});
