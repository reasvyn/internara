<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('W8K2P / H9T4N: Deployment Artisan Commands', function (): void {
    test('W8K2P-FR-DOCK-020: deploy:detect runs server capability probes and outputs textual recommendation', function (): void {
        $this->artisan('deploy:detect')
            ->assertSuccessful()
            ->expectsOutputToContain('Recommended Profile:');
    });

    test('W8K2P-FR-DOCK-021: deploy:detect with --json option returns structured probe metrics', function (): void {
        Artisan::call('deploy:detect', ['--json' => true]);
        $output = Artisan::output();

        $data = json_decode($output, true);
        expect($data)->toBeArray()
            ->and($data)->toHaveKeys(['recommended_profile', 'probes', 'override'])
            ->and($data['probes'])->toHaveKeys(['container', 'redis', 'daemon', 'composer'])
            ->and($data['recommended_profile'])->toBeIn(['shared-hosting', 'vps-docker']);
    });

    test('H9T4N-FR-HOST-010: deploy:configure rejects unknown profile with failure code', function (): void {
        $exitCode = Artisan::call('deploy:configure', ['--profile' => 'non-existent-profile']);
        $output = Artisan::output();

        expect($exitCode)->toBe(1)
            ->and($output)->toContain('Invalid deployment profile');
    });

    test('H9T4N-FR-HOST-011: deploy:configure applies preset drivers to target env file', function (): void {
        $tempEnv = tempnam(sys_get_temp_dir(), 'env_test_');
        File::put($tempEnv, "QUEUE_CONNECTION=sync\nCACHE_STORE=file\nSESSION_DRIVER=file\n");

        try {
            $exitCode = Artisan::call('deploy:configure', [
                '--profile' => 'shared-hosting',
                '--env-path' => $tempEnv,
            ]);

            expect($exitCode)->toBe(0);

            $contents = File::get($tempEnv);
            expect($contents)->toContain('QUEUE_CONNECTION=')
                ->and($contents)->toContain('CACHE_STORE=');
        } finally {
            if (File::exists($tempEnv)) {
                File::delete($tempEnv);
            }
        }
    });
});
