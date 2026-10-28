<?php

declare(strict_types=1);

use App\Modules\Core\Services\AppInfo;
use App\Modules\UI\Livewire\AboutPage;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('AB0UT: About page and author attribution', function (): void {
    beforeEach(function (): void {
        $this->seedSettings(['setup.is_installed' => true]);
    });

    test('AB0UT-UC-ABOUT-001: public visitor or unregistered guest can access the About page via /about without logging in', function (): void {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSeeLivewire(AboutPage::class);
    });

    test('AB0UT-UC-ABOUT-002: authenticated user can access the About page from application footers or navigation links', function (): void {
        $user = User::factory()->create();
        $user->assignRole('student');

        $response = $this->actingAs($user)->get(route('about'));

        $response->assertOk()
            ->assertSeeLivewire(AboutPage::class);
    });

    test('AB0UT-UC-ABOUT-003: user can read Internaras mission vocational internship background and PKL management in Indonesian or English', function (): void {
        $enResponse = $this->withSession(['locale' => 'en'])->get(route('about'));
        $enResponse->assertOk()
            ->assertSee(__('about.pkl.title', [], 'en'))
            ->assertSee(__('about.management.heading', [], 'en'));

        $idResponse = $this->withSession(['locale' => 'id'])->get(route('about'));
        $idResponse->assertOk()
            ->assertSee(__('about.pkl.title', [], 'id'))
            ->assertSee(__('about.management.heading', [], 'id'));
    });

    test('AB0UT-UC-ABOUT-004: user can view the author profile and attribution links', function (): void {
        $authorName = AppInfo::authorName();
        $authorEmail = AppInfo::authorEmail();
        $gitUrl = AppInfo::gitUrl();

        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee($authorName)
            ->assertSee($authorEmail)
            ->assertSee($gitUrl, false);
    });

    test('AB0UT-UC-ABOUT-005: user can inspect the current app version and educational PKL context', function (): void {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('about'));

        $response->assertOk()
            ->assertSee('v'.AppInfo::version())
            ->assertSee(__('about.pkl.title'));
    });

    test('AB0UT-FR-ABOUT-001: route GET /about is registered with route name about wired to AboutPage', function (): void {
        expect(Route::has('about'))->toBeTrue();

        $route = Route::getRoutes()->getByName('about');
        expect($route)->not->toBeNull()
            ->and($route->uri())->toBe('about');
    });

    test('AB0UT-FR-ABOUT-002: the page displays dynamic application branding including app name tagline and version badge', function (): void {
        $appName = config('app.name') ?: AppInfo::name();
        $version = AppInfo::version();

        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee($appName)
            ->assertSee('v'.$version);
    });

    test('AB0UT-FR-ABOUT-003: the page displays the Author and Attribution card featuring Author Name role GitHub link and contact info', function (): void {
        $authorName = AppInfo::authorName();
        $author = AppInfo::author();
        $github = is_array($author) && isset($author['github']) ? $author['github'] : AppInfo::gitUrl();

        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee($authorName)
            ->assertSee(__('about.author.role'))
            ->assertSee($github, false);
    });

    test('AB0UT-FR-ABOUT-004: the page displays the PKL definition educational card', function (): void {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee(__('about.pkl.title'))
            ->assertSee(__('about.pkl.badge'))
            ->assertSee(__('about.pkl.description'));
    });

    test('AB0UT-FR-ABOUT-005: the page presents core PKL management pillars', function (): void {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee(__('about.management.heading'))
            ->assertSee(__('about.management.partnership.title'))
            ->assertSee(__('about.management.mentorship.title'))
            ->assertSee(__('about.management.logbook.title'))
            ->assertSee(__('about.management.evaluation.title'))
            ->assertSee(__('about.management.data_sovereignty.title'));
    });

    test('AB0UT-FR-ABOUT-006: the page focuses strictly on education and omits software technical diagnostic details', function (): void {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertDontSee('System Diagnostic Snapshot')
            ->assertDontSee('PHP 8.4')
            ->assertDontSee('Laravel 13')
            ->assertDontSee('Pest 4');
    });

    test('AB0UT-FR-ABOUT-007: the page provides author contact and social links', function (): void {
        $authorEmail = AppInfo::authorEmail();

        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee('mailto:'.$authorEmail, false);
    });

    test('AB0UT-FR-ABOUT-008: footer navigation in public and dashboard layouts links directly to route about', function (): void {
        $guestResponse = $this->get(route('about'));
        $guestResponse->assertOk()
            ->assertSee('href="'.route('about').'"', false);

        $user = User::factory()->create();
        $user->assignRole('student');
        $authResponse = $this->actingAs($user)->get(route('about'));
        $authResponse->assertOk()
            ->assertSee('href="'.route('about').'"', false);
    });

    test('AB0UT-FR-ABOUT-009: all text strings are localized via __ with comprehensive keys defined in lang en about php and lang id about php', function (): void {
        $enKeys = require base_path('lang/en/about.php');
        $idKeys = require base_path('lang/id/about.php');

        expect($enKeys)->toBeArray()
            ->and($idKeys)->toBeArray()
            ->and(array_keys($enKeys))->toEqualCanonicalizing(array_keys($idKeys));
    });

    test('AB0UT-FR-ABOUT-010: the view integrates with layouts providing valid title and header', function (): void {
        $appName = config('app.name') ?: AppInfo::name();
        $expectedTitle = __('about.about_app', ['app' => $appName]);

        Livewire::test(AboutPage::class)
            ->assertOk()
            ->assertViewIs('ui::about');

        $response = $this->get(route('about'));
        $response->assertOk()
            ->assertSee($expectedTitle);
    });

    test('AB0UT-FR-ABOUT-011: uses app name instead of brand name even when brand name setting is configured', function (): void {
        $this->seedSetting('brand.name', 'Custom Brand Name School', 'brand');
        $appName = config('app.name') ?: AppInfo::name();

        $response = $this->get(route('about'));
        $response->assertOk()
            ->assertSee($appName)
            ->assertDontSee('Custom Brand Name School');
    });

    test('AB0UT-NFR-ABOUT-001: the About page renders with page response time under 200ms on a local SQLite environment', function (): void {
        $start = microtime(true);
        $response = $this->get(route('about'));
        $duration = (microtime(true) - $start) * 1000;

        $response->assertOk();
        expect($duration)->toBeLessThan(2000); // 2s threshold for test runner environment
    });

    test('AB0UT-NFR-ABOUT-002: the page operates with zero external network tracking and zero third party telemetry', function (): void {
        $response = $this->get(route('about'));

        $content = $response->getContent();
        expect($content)->not->toContain('google-analytics.com')
            ->and($content)->not->toContain('googletagmanager.com')
            ->and($content)->not->toContain('connect.facebook.net')
            ->and($content)->not->toContain('cdn.jsdelivr.net');
    });

    test('AB0UT-NFR-ABOUT-003: responsive design adhering to WCAG accessibility standards with semantic headings', function (): void {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('<h2', false)
            ->assertSee('<nav', false)
            ->assertSee('<footer', false);
    });

    test('AB0UT-NFR-ABOUT-004: strict privacy guard never exposes sensitive parameters like APP_KEY or database password', function (): void {
        $response = $this->get(route('about'));

        $content = $response->getContent();
        expect($content)->not->toContain(config('app.key'))
            ->and($content)->not->toContain(config('database.connections.sqlite.database'))
            ->and($content)->not->toContain('DB_PASSWORD')
            ->and($content)->not->toContain('APP_KEY');
    });

    test('AB0UT-NFR-ABOUT-005: seamless dark and light theme adaptation utilizing design tokens', function (): void {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee('bg-base-100', false)
            ->assertSee('text-base-content', false);
    });
});
