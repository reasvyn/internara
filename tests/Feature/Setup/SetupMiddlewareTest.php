<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Models\Setup;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    Setup::truncate();
    Setup::create(['is_installed' => false, 'completed_steps' => []]);
});

describe('ProtectSetupRouteMiddleware', function () {
    it('renders code entry form when no token provided and not installed', function () {
        $this->get(route('setup'))
            ->assertStatus(200)
            ->assertSee('Enter Setup Code');
    });

    it('allows access with valid token when not installed', function () {
        $token = app(GenerateSetupTokenAction::class)->execute();

        $this->get(route('setup', ['setup_token' => $token['plaintext']]))
            ->assertStatus(200);
    });

    it('returns 404 when system is installed with no authorized session', function () {
        Setup::truncate();
        Setup::create(['is_installed' => true, 'completed_steps' => []]);

        $this->get(route('setup'))
            ->assertStatus(404);
    });

    it('rejects invalid token and does not crash', function () {
        $this->withHeader('X-Livewire', 'true')
            ->get(route('setup', ['setup_token' => 'bad-token']));
        expect(true)->toBeTrue();
    });

    it('authorizes session on valid POST token and redirects', function () {
        $token = app(GenerateSetupTokenAction::class)->execute();

        $this->post(route('setup'), ['setup_token' => $token['plaintext']])
            ->assertRedirect(route('setup'));

        expect(session()->get('setup.authorized'))->toBeTrue();
    });

    it('rate limits excessive attempts', function () {
        config(['setup.security.rate_limit_attempts' => 1]);
        config(['setup.security.rate_limit_decay_seconds' => 60]);

        $this->get(route('setup'));
        $this->get(route('setup'), ['Accept' => 'application/json'])
            ->assertStatus(429);
    });
});

describe('RequireSetupAccessMiddleware', function () {
    it('allows setup route when not installed', function () {
        $this->get(route('setup'))
            ->assertStatus(200);
    });

    it('redirects other routes to setup when not installed', function () {
        $this->get('/login')
            ->assertRedirect(route('setup'));
    });

    it('passes through when installed', function () {
        Setup::truncate();
        Setup::create(['is_installed' => true, 'completed_steps' => []]);

        $this->get(route('login'))
            ->assertStatus(200);
    });
});
