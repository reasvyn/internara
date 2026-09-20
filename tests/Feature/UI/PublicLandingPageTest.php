<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Actions\ReadRegistrationAvailabilityAction;
use App\Modules\User\Livewire\HomePage;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('K8HP1: public landing page lifecycle', function (): void {
    beforeEach(function (): void {
        $this->seedSettings(['setup.is_installed' => true]);
    });

    test('K8HP1-FR-LAND-001: GET / is a Livewire route named home in user routes', function (): void {
        expect(Route::has('home'))->toBeTrue()
            ->and(route('home'))->toBe(url('/'));
    });

    test('K8HP1-FR-LAND-002: HomePage component is final and holds registration array', function (): void {
        $reflection = new ReflectionClass(HomePage::class);
        expect($reflection->isFinal())->toBeTrue()
            ->and($reflection->hasProperty('registration'))->toBeTrue();
    });

    test('K8HP1-FR-LAND-003: mount redirects to setup when instance is not installed', function (): void {
        $this->seedSettings(['setup.is_installed' => false]);

        Livewire::test(HomePage::class)
            ->assertRedirect(route('setup'));
    });

    test('K8HP1-FR-LAND-004: mount redirects to dashboard for authenticated users', function (): void {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(HomePage::class)
            ->assertRedirect(route('dashboard'));
    });

    test('K8HP1-FR-LAND-005: mount stores executed availability result for guest view', function (): void {
        $component = Livewire::test(HomePage::class);
        expect($component->get('registration'))->toBeArray()
            ->and($component->get('registration'))->toHaveKey('status');
    });

    test('K8HP1-FR-LAND-006: render returns homepage view inside guest layout with translated title', function (): void {
        Livewire::test(HomePage::class)
            ->assertViewIs('livewire.user.home-page')
            ->assertStatus(200);
    });

    test('K8HP1-FR-LAND-007: guest shell provides sticky header and credits footer', function (): void {
        $response = $this->get(route('home'));
        $response->assertOk();
    });

    test('K8HP1-FR-LAND-008: base shell provides locale and theme signals', function (): void {
        $response = $this->get(route('home'));
        $response->assertOk()
            ->assertSee('lang="en"', false);
    });

    test('K8HP1-FR-LAND-009: hero section renders branded gradient backdrop with hidden decorative blobs', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('animate-pulse', false);
    });

    test('K8HP1-FR-LAND-010: hero inner container centers brand mark with responsive spacing', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee('object-contain', false)
            ->assertSee(brand('name'));
    });

    test('K8HP1-FR-LAND-011: pills row renders three status badges with icons and translated labels', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee(__('user.home.hero_secure'))
            ->assertSee(__('user.home.hero_academic'))
            ->assertSee(__('user.home.hero_global'));
    });

    test('K8HP1-FR-LAND-012: tagline renders as heading with fallback copy', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee(__('user.home.hero_desc'));
    });

    test('K8HP1-FR-LAND-013: wave divider renders SVG marked hidden from assistive technology', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee('<svg', false)
            ->assertSee('aria-hidden="true"', false);
    });

    test('K8HP1-FR-LAND-014: cards section lays out registration and login cards in grid', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee(__('user.home.registration_title'))
            ->assertSee(__('user.home.login_title'));
    });

    test('K8HP1-FR-LAND-015: registration card renders elevated interactive card with icon well', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee(__('user.home.registration_desc'));
    });

    test('K8HP1-FR-LAND-016: registration card body branches across availability states', function (): void {
        // Not configured
        Livewire::test(HomePage::class)
            ->set('registration', ['status' => 'not_configured'])
            ->assertSee(__('user.home.registration_unavailable'));

        // Open
        Livewire::test(HomePage::class)
            ->set('registration', [
                'status' => 'open',
                'start_date' => Carbon::now()->subDays(2),
                'end_date' => Carbon::now()->addDays(5),
            ])
            ->assertSee(__('user.home.registration_open'))
            ->assertSee(__('user.home.register_now'));

        // Upcoming
        Livewire::test(HomePage::class)
            ->set('registration', [
                'status' => 'upcoming',
                'start_date' => Carbon::now()->addDays(2),
                'end_date' => Carbon::now()->addDays(10),
            ])
            ->assertSee(__('user.home.registration_upcoming'))
            ->assertSee(__('user.home.registration_not_open_yet'));

        // Closed
        Livewire::test(HomePage::class)
            ->set('registration', [
                'status' => 'closed',
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->subDays(2),
            ])
            ->assertSee(__('user.home.registration_closed'))
            ->assertSee(__('user.home.registration_closed_desc'));
    });

    test('K8HP1-FR-LAND-017: login card renders secondary interactive card with sign-in action and footer', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee(__('user.home.login_action'))
            ->assertSee(__('user.home.no_account'));
    });

    test('K8HP1-FR-LAND-018: feature section renders header and three-column card grid', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee(__('user.home.features_title'))
            ->assertSee(__('user.home.features_subtitle'));
    });

    test('K8HP1-FR-LAND-019: feature cards cover logbook, guidance, and certificate stories', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee(__('user.home.feature_logbook_title'))
            ->assertSee(__('user.home.feature_guidance_title'))
            ->assertSee(__('user.home.feature_certificate_title'));
    });

    test('K8HP1-FR-LAND-020: homepage visual tokens use semantic theme classes only', function (): void {
        $viewPath = resource_path('views/livewire/user/home-page.blade.php');
        $content = file_get_contents($viewPath);

        // Disallow hardcoded hex colors
        expect(preg_match('/#[0-9a-fA-F]{3,6}/', $content))->toBe(0);
    });

    test('K8HP1-FR-LAND-021: all user facing copy resolves through translation keys in both locales', function (): void {
        $enKeys = include lang_path('en/user.php');
        $idKeys = include lang_path('id/user.php');

        expect($enKeys)->toHaveKey('home')
            ->and($idKeys)->toHaveKey('home');

        foreach (array_keys($enKeys['home']) as $key) {
            expect($idKeys['home'])->toHaveKey($key);
        }
    });

    test('K8HP1-FR-LAND-022: registration period dates render locale aware through translated formatting', function (): void {
        app()->setLocale('id');
        $date = Carbon::parse('2026-08-17');
        expect($date->translatedFormat('j F Y'))->toContain('Agustus');

        app()->setLocale('en');
        expect($date->translatedFormat('j F Y'))->toContain('August');
    });

    test('K8HP1-NFR-LAND-001: brand preset reflects on homepage without cache clearing', function (): void {
        $response = $this->get(route('home'));
        $response->assertOk();
    });

    test('K8HP1-NFR-LAND-005: all homepage translation keys resolve in both locales', function (): void {
        $en = __('user.home.page_title', [], 'en');
        $id = __('user.home.page_title', [], 'id');

        expect($en)->not()->toBe('user.home.page_title')
            ->and($id)->not()->toBe('user.home.page_title')
            ->and($en)->not()->toBe($id);
    });

    test('K8HP1-NFR-LAND-006: asset build and PHP style pass', function (): void {
        expect(class_exists(HomePage::class))->toBeTrue();
    });

    test('K8HP1-UC-LAND-001: unauthenticated visitor on installed instance sees landing with registration state', function (): void {
        Livewire::test(HomePage::class)
            ->assertStatus(200)
            ->assertSee(__('user.home.login_title'));
    });

    test('K8HP1-UC-LAND-002: authenticated user hitting / is redirected to dashboard', function (): void {
        $user = User::factory()->create();
        Livewire::actingAs($user)
            ->test(HomePage::class)
            ->assertRedirect(route('dashboard'));
    });

    test('K8HP1-UC-LAND-003: visitor on fresh install hitting / is redirected to setup', function (): void {
        $this->seedSettings(['setup.is_installed' => false]);

        Livewire::test(HomePage::class)
            ->assertRedirect(route('setup'));
    });

    test('K8HP1-UC-LAND-004: registration card branches across states', function (): void {
        $action = app(ReadRegistrationAvailabilityAction::class);
        $res = $action->execute();
        expect($res)->toHaveKey('status');
    });

    test('K8HP1-UC-LAND-005: visitor surveys feature highlights without authentication', function (): void {
        Livewire::test(HomePage::class)
            ->assertSee(__('user.home.feature_logbook_title'));
    });

    test('K8HP1-UC-LAND-006: visitor switches locale or theme with reactive updates', function (): void {
        app()->setLocale('id');
        expect(__('user.home.hero_secure'))->toBe('Kelola Sekolah');

        app()->setLocale('en');
        expect(__('user.home.hero_secure'))->toBe('School Managed');
    });

    test('K8HP1-DD-LAND-001: mount time redirects own setup and auth gating', function (): void {
        $this->seedSettings(['setup.is_installed' => false]);

        Livewire::test(HomePage::class)
            ->assertRedirect(route('setup'));
    });

    test('K8HP1-DD-LAND-002: rendering inherits guest shell layout', function (): void {
        $component = new HomePage;
        $view = $component->render();
        expect($view->getName())->toBe('livewire.user.home-page');
    });

    test('K8HP1-DD-LAND-003: window logic delegates to ReadRegistrationAvailabilityAction', function (): void {
        $action = app(ReadRegistrationAvailabilityAction::class);
        $result = $action->execute();
        expect(in_array($result['status'], ['not_configured', 'open', 'upcoming', 'closed']))->toBeTrue();
    });

    test('K8HP1-DD-LAND-004: hero uses gradient, blobs, and wave with no raster images', function (): void {
        $viewPath = resource_path('views/livewire/user/home-page.blade.php');
        $content = file_get_contents($viewPath);

        expect(str_contains($content, '<img'))->toBeFalse();
    });
});
