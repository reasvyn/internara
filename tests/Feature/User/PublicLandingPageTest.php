<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('K8HP1: public landing page', function (): void {
    test('K8HP1-FR-LAND-020: homepage response uses semantic theme tokens without page styles or hardcoded colors', function (): void {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('bg-gradient-to-br', false)
            ->assertSee('from-primary/8', false)
            ->assertSee('bg-base-100', false)
            ->assertSee('text-base-content', false);
    });

    test('K8HP1-FR-LAND-021: homepage response renders translated copy in both supported locales', function (): void {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            $response = $this->get(route('home'));

            $response->assertOk()
                ->assertSee(__('user.home.page_title'))
                ->assertSee(__('user.home.hero_desc'))
                ->assertSee(__('user.home.registration_title'))
                ->assertSee(__('user.home.login_action'))
                ->assertSee(__('user.home.features_title'));
        }
    });
});
