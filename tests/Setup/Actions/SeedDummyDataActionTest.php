<?php

declare(strict_types=1);

use App\Modules\Setup\Domain\Installation\Actions\SeedDummyDataAction;
use App\Modules\Setup\Domain\Installation\Console\Commands\SetupInstallCommand;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('3UOZP-FR-C10: seeds the demo dataset via DummySeeder after provisioning', function () {
    $seeded = app(SeedDummyDataAction::class)->execute();

    expect($seeded)->toBeTrue();

    expect(User::firstWhere('email', config('dummy.accounts.admin_email')))->not->toBeNull();
});

test('3UOZP-NFR-S13: skips the demo seed in production without failing the install', function () {
    app()->detectEnvironment(fn () => 'production');

    try {
        $seeded = app(SeedDummyDataAction::class)->execute();

        expect($seeded)->toBeFalse();
        expect(User::count())->toBe(0);
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});

test('3UOZP-FR-C10: setup:install exposes the --with-dummy option', function () {
    $command = app(SetupInstallCommand::class);
    $signature = (new ReflectionClass($command))->getProperty('signature');

    expect($signature->getValue($command))->toContain('--with-dummy');
});
