<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\User\Models\User;

beforeEach(function () {
    app()->setLocale('en');
});

describe('AutoInactivateAccounts', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('accounts:auto-inactivate');
    });

    it('reports no inactive accounts when none found', function () {
        $this->artisan('accounts:auto-inactivate')
            ->assertExitCode(0)
            ->expectsOutputToContain('No inactive accounts');
    });

    it('lists accounts in dry-run mode without inactivating', function () {
        $user = User::factory()->create();
        $user->setStatus(AccountStatus::VERIFIED);

        $this->artisan('accounts:auto-inactivate', ['--dry-run' => true])
            ->assertExitCode(0);

        expect($user->fresh()->statuses()->latest()->first()->name)->toBe(AccountStatus::VERIFIED->value);
    });
});
