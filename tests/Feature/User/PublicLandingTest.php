<?php

declare(strict_types=1);

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('K8HP1: public landing', function (): void {
    beforeEach(function (): void {
        $this->seedSettings(['setup.is_installed' => true]);
    });

    test('K8HP1-FR-LAND-022: guest landing renders registration and login journeys', function (): void {
        $this->seedSettings([
            'registration_period_start' => now()->subDay()->toDateString(),
            'registration_period_end' => now()->addDay()->toDateString(),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee(__('user.home.registration_title'))
            ->assertSee(__('user.home.registration_open'))
            ->assertSee(__('user.home.login_title'))
            ->assertSee(__('user.home.login_action'))
            ->assertSee('href="'.route('apply').'"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee(__('user.home.feature_logbook_title'))
            ->assertSee(__('user.home.feature_guidance_title'))
            ->assertSee(__('user.home.feature_certificate_title'))
            ->assertDontSee('notifications');
    });

    test('K8HP1-FR-LAND-022: landing renders closed state without registration action', function (): void {
        $this->seedSettings([
            'registration_period_start' => now()->subMonths(3)->toDateString(),
            'registration_period_end' => now()->subMonths(2)->toDateString(),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee(__('user.home.registration_closed'))
            ->assertSee(__('user.home.registration_closed_desc'))
            ->assertDontSee('href="'.route('apply').'"', false)
            ->assertSee('href="'.route('login').'"', false);
    });

    test('K8HP1-FR-LAND-022: authenticated visitors are redirected to their dashboard', function (): void {
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    });
});
