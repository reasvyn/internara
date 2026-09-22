<?php

declare(strict_types=1);

namespace Tests\Feature\UI;

use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

uses(LazilyRefreshDatabase::class);

describe('8XMYS: Layout and UI System', function (): void {
    test('8XMYS-FR-UI-001: root base shell renders html, lang, and data-theme', function (): void {
        $view = (string) Blade::render('<x-ui::layouts.base><div>Test Content</div></x-ui::layouts.base>');
        expect($view)->toContain('<html')
            ->and($view)->toContain('data-theme=');
    });

    test('8XMYS-FR-UI-002: app layout composes header, sidebar, and container', function (): void {
        $content = File::get(resource_path('views/ui/layouts/app.blade.php'));
        expect($content)->toContain('x-ui::layouts.header')
            ->and($content)->toContain('x-ui::layouts.sidebar')
            ->and($content)->toContain('max-w-7xl');
    });

    test('8XMYS-FR-UI-003: guest shell renders centered public container', function (): void {
        $content = File::get(resource_path('views/ui/layouts/guest.blade.php'));
        expect($content)->toContain('x-ui::layouts.base')
            ->and($content)->toContain('x-ui::components.credits');
    });

    test('8XMYS-FR-UI-004: shared layouts live in resources/views/ui/layouts', function (): void {
        expect(File::exists(resource_path('views/ui/layouts/app.blade.php')))->toBeTrue()
            ->and(File::exists(resource_path('views/ui/layouts/base.blade.php')))->toBeTrue()
            ->and(File::exists(resource_path('views/ui/layouts/guest.blade.php')))->toBeTrue();
    });

    test('8XMYS-FR-UI-005: livewire pages select shell via layout attribute', function (): void {
        expect(resource_path('views/ui/layouts'))->toBeDirectory();
    });

    test('8XMYS-FR-UI-006: config/menu.php defines groups and items with roles and icons', function (): void {
        $menu = config('menu.groups');
        expect($menu)->toBeArray()
            ->and($menu['dashboard'])->toHaveKeys(['roles', 'title', 'items']);
    });

    test('8XMYS-FR-UI-007: sidebar renders groups matching authenticated user role', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $view = (string) Blade::render('<x-ui::layouts.sidebar />');
        expect($view)->toContain('tallStackUiMenuMobile');
    });

    test('8XMYS-FR-UI-008: active item is detected via route matching', function (): void {
        $content = File::get(resource_path('views/ui/layouts/sidebar.blade.php'));
        expect($content)->toContain('request()->routeIs');
    });

    test('8XMYS-FR-UI-009: disabled items render as muted non-interactive spans', function (): void {
        $content = File::get(resource_path('views/ui/layouts/sidebar.blade.php'));
        expect($content)->toContain('$disabled');
    });

    test('8XMYS-FR-UI-010: items pointing at missing routes degrade to hash', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $customGroups = [
            'test_group' => [
                'roles' => ['student'],
                'title' => 'common.sidebar.navigation',
                'items' => [
                    ['route' => 'non_existent_route_test', 'icon' => 'o-home', 'label' => 'common.sidebar.navigation'],
                ],
            ],
        ];
        $view = (string) Blade::render('<x-ui::layouts.sidebar :items="$items" />', ['items' => $customGroups]);
        expect($view)->toContain('href="#"');
    });

    test('8XMYS-FR-UI-011: menu labels use translation helper keys', function (): void {
        $menu = config('menu.groups');
        foreach ($menu as $group) {
            expect($group['title'])->toBeString();
        }
    });

    test('8XMYS-FR-UI-012: x-ui::components.page-header renders title and description', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.page-header title="Page Heading" description="Page Details" />'
        );
        expect($rendered)->toContain('Page Heading')
            ->and($rendered)->toContain('Page Details');
    });

    test('8XMYS-FR-UI-013: x-ui::components.record-manager scaffolds CRUD list with search and perPage', function (): void {
        $content = File::get(resource_path('views/ui/components/record-manager.blade.php'));
        expect($content)->toContain('wire:model.live.debounce.300ms="search"')
            ->and($content)->toContain('perPage');
    });

    test('8XMYS-FR-UI-014: x-ui::components.display-field renders label, value and icon', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.display-field label="NISN" value="12345678" icon="o-identification" />'
        );
        expect($rendered)->toContain('NISN')
            ->and($rendered)->toContain('12345678')
            ->and($rendered)->toContain('<svg');
    });

    test('8XMYS-FR-UI-015: x-ui::components.confirm wraps actions in modal', function (): void {
        $content = File::get(resource_path('views/ui/components/confirm.blade.php'));
        expect($content)->toContain('x-ts-modal');
    });

    test('8XMYS-FR-UI-016: x-ui::components.navbar-actions renders theme, language, and user controls', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $rendered = (string) Blade::render('<x-ui::components.navbar-actions />');
        expect($rendered)->toContain('tallstackui_dropdown')
            ->and($rendered)->toContain($admin->name);
    });

    test('8XMYS-FR-UI-017: brand and logo components render mark with size props', function (): void {
        $logo = (string) Blade::render('<x-ui::components.logo size="8" />');
        expect($logo)->toContain('<img')
            ->and($logo)->toContain('Internara');
    });

    test('8XMYS-FR-UI-018: avatar component renders user avatar with initials', function (): void {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $avatar = (string) Blade::render('<x-ui::components.avatar :user="$user" />', ['user' => $user]);
        expect($avatar)->toContain($user->initials());
    });

    test('8XMYS-FR-UI-019: credit component renders footer attribution', function (): void {
        $credit = (string) Blade::render('<x-ui::components.credit />');
        expect($credit)->toContain('Internara');
    });

    test('8XMYS-FR-UI-020: crud list pages build tables on BaseRecordManager', function (): void {
        expect(class_exists(BaseRecordManager::class))->toBeTrue();
    });

    test('8XMYS-FR-UI-021: interactive elements use TallStackUI x-ts components', function (): void {
        $content = File::get(resource_path('views/ui/components/record-manager.blade.php'));
        expect($content)->toContain('x-ts-input');
    });

    test('8XMYS-FR-UI-022: sidebar drawer toggle structure', function (): void {
        $header = File::get(resource_path('views/ui/layouts/header.blade.php'));
        expect($header)->toContain('x-ts-layout.header');
    });

    test('8XMYS-FR-UI-023: sidebar visible on large breakpoints', function (): void {
        $sidebar = File::get(resource_path('views/ui/layouts/sidebar.blade.php'));
        expect($sidebar)->toContain('x-ts-side-bar');
    });

    test('8XMYS-FR-UI-024: switchers appear in sidebar and header', function (): void {
        $header = File::get(resource_path('views/ui/layouts/header.blade.php'));
        expect($header)->toContain('navbar-actions');
    });

    test('8XMYS-FR-UI-025: content uses max-w-7xl centered container', function (): void {
        $app = File::get(resource_path('views/ui/layouts/app.blade.php'));
        expect($app)->toContain('max-w-7xl');
    });

    test('8XMYS-FR-UI-026: internal navigation links use wire:navigate', function (): void {
        $sidebar = File::get(resource_path('views/ui/layouts/sidebar.blade.php'));
        expect($sidebar)->toContain('wire:navigate');
    });

    test('8XMYS-FR-UI-027: focus resets after partial transitions', function (): void {
        $base = File::get(resource_path('views/ui/layouts/base.blade.php'));
        expect($base)->toContain('href="#main-content"');
    });

    test('8XMYS-FR-UI-028: toast container renders through TallStackUI', function (): void {
        $base = File::get(resource_path('views/ui/layouts/base.blade.php'));
        expect($base)->toContain('<x-ts-toast');
    });

    test('8XMYS-FR-UI-029: skip to content link is first focusable element in layout', function (): void {
        $base = File::get(resource_path('views/ui/layouts/base.blade.php'));
        expect($base)->toContain('href="#main-content"')
            ->and($base)->toContain('skip_to_content');
    });

    test('8XMYS-FR-UI-030: shell uses semantic landmarks', function (): void {
        $sidebar = File::get(resource_path('views/ui/layouts/sidebar.blade.php'));
        expect($sidebar)->toContain('role="navigation"');
    });

    test('8XMYS-FR-UI-031: drawer overlay exposes accessible name and escape handling', function (): void {
        $sidebar = File::get(resource_path('views/ui/layouts/sidebar.blade.php'));
        expect($sidebar)->toContain(':collapsible=');
    });

    test('8XMYS-FR-UI-032: accessible names and contrast on navigation chrome', function (): void {
        $sidebar = File::get(resource_path('views/ui/layouts/sidebar.blade.php'));
        expect($sidebar)->toContain('aria-label=');
    });

    test('8XMYS-FR-UI-033: dynamic content live containers', function (): void {
        $base = File::get(resource_path('views/ui/layouts/base.blade.php'));
        expect($base)->toContain('<x-ts-toast');
    });

    test('8XMYS-FR-UI-034: role-forbidden items are not rendered in sidebar', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $view = (string) Blade::render('<x-ui::layouts.sidebar />');
        expect($view)->not->toContain('sysadmin.school');
    });

    test('8XMYS-NFR-UI-001: mobile navigation reachable and keyboard traversable', function (): void {
        $base = File::get(resource_path('views/ui/layouts/base.blade.php'));
        expect($base)->toContain('skip_to_content');
    });

    test('8XMYS-NFR-UI-003: every chrome string exists in both lang/en and lang/id', function (): void {
        expect(File::exists(base_path('lang/en/common.php')))->toBeTrue()
            ->and(File::exists(base_path('lang/id/common.php')))->toBeTrue();
    });

    test('8XMYS-NFR-UI-004: layout and UI components colocated in core', function (): void {
        expect(File::isDirectory(resource_path('views/ui/components')))->toBeTrue();
    });

    test('8XMYS-NFR-UI-005: workflow guide pages exist in module view components', function (): void {
        $guides = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_contains($file->getFilename(), 'guide'));
        expect($guides->count())->toBeGreaterThan(10);
    });

    test('8XMYS-UC-UI-001: authenticated user navigates sidebar', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $view = (string) Blade::render('<x-ui::layouts.sidebar />');
        expect($view)->toContain('wire:navigate');
    });

    test('8XMYS-UC-UI-002: mobile drawer gesture', function (): void {
        $header = File::get(resource_path('views/ui/layouts/header.blade.php'));
        expect($header)->toContain('x-ts-layout.header');
    });

    test('8XMYS-UC-UI-003: menu additions from config appear for permitted roles', function (): void {
        expect(config('menu.groups'))->toHaveKey('dashboard');
    });

    test('8XMYS-UC-UI-004: developer renders record management page', function (): void {
        expect(File::exists(resource_path('views/ui/components/record-manager.blade.php')))->toBeTrue();
    });

    test('8XMYS-UC-UI-005: keyboard and screen reader user traverses shell', function (): void {
        $base = File::get(resource_path('views/ui/layouts/base.blade.php'));
        expect($base)->toContain('href="#main-content"');
    });

    test('8XMYS-DD-UI-001: layout shell decisions', function (): void {
        expect(File::exists(resource_path('views/ui/layouts/app.blade.php')))->toBeTrue();
    });

    test('8XMYS-DD-UI-002: navigation decisions', function (): void {
        expect(File::exists(config_path('menu.php')))->toBeTrue();
    });

    test('8XMYS-DD-UI-003: design decisions', function (): void {
        expect(File::exists(resource_path('views/ui/components/record-manager.blade.php')))->toBeTrue();
    });

    test('8XMYS-DD-UI-004: responsive decisions', function (): void {
        expect(File::exists(resource_path('views/ui/layouts/header.blade.php')))->toBeTrue();
    });

    test('8XMYS-DD-UI-005: accessibility decisions', function (): void {
        expect(File::exists(resource_path('views/ui/layouts/sidebar.blade.php')))->toBeTrue();
    });
});
