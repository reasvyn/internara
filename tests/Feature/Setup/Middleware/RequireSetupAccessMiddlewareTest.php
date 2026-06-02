<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Middleware;

use App\Domain\Setup\Models\Setup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
});

describe('RequireSetupAccessMiddleware', function () {
    it('allows access to setup route when not installed', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->get(route('setup'))
            ->assertStatus(200);
    });

    it('redirects non-setup routes to setup when not installed', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->get('/login')
            ->assertRedirect(route('setup'));
    });

    it('allows all routes when installed', function () {
        Setup::factory()->installed()->create();

        $this->get('/login')
            ->assertStatus(200);
    });

    it('allows static assets even when not installed', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->get('/build/manifest.json')
            ->assertStatus(404);
    });

    it('handles Livewire requests even when not installed', function () {
        Setup::factory()->create(['is_installed' => false]);

        $this->withHeader('X-Livewire', 'true')
            ->get(route('setup'))
            ->assertStatus(403);
    });
});
