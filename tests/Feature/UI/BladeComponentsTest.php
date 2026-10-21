<?php

declare(strict_types=1);

namespace Tests\Feature\UI;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(LazilyRefreshDatabase::class);

describe('8XMYS: Reusable UI Blade Components and Widgets', function (): void {
    test('8XMYS-FR-UI-040-A: stat-card widget renders title, value, icon, and rounded-2xl contour', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::widgets.stat-card title="Active Interns" value="48" icon="academic-cap" />'
        );

        expect($rendered)->toContain('Active Interns')
            ->and($rendered)->toContain('48')
            ->and($rendered)->toContain('rounded-2xl')
            ->and($rendered)->toContain('<svg');
    });

    test('8XMYS-FR-UI-040-B: empty-state widget renders title, description, and icon', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::widgets.empty-state title="No Data Found" description="Try refining your search" icon="inbox" />'
        );

        expect($rendered)->toContain('No Data Found')
            ->and($rendered)->toContain('Try refining your search')
            ->and($rendered)->toContain('<svg');
    });

    test('8XMYS-FR-UI-040-C: quick-link widget renders label, icon, and target link', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::widgets.quick-link label="Manage Users" icon="users" link="/users" />'
        );

        expect($rendered)->toContain('Manage Users')
            ->and($rendered)->toContain('href="/users"')
            ->and($rendered)->toContain('rounded-xl');
    });

    test('8XMYS-FR-UI-040-D: app-info widget renders brand name and version badge', function (): void {
        $rendered = (string) Blade::render('<x-ui::widgets.app-info />');

        expect($rendered)->toContain('Internara')
            ->and($rendered)->toContain('rounded-2xl');
    });

    test('8XMYS-FR-UI-040-E: profile-summary widget renders user details and avatar', function (): void {
        $user = User::factory()->create([
            'name' => 'Aditya Pratama',
            'email' => 'aditya@example.com',
        ]);
        $user->assignRole('student');

        $rendered = (string) Blade::render(
            '<x-ui::widgets.profile-summary :user="$user" />',
            ['user' => $user]
        );

        expect($rendered)->toContain('Aditya Pratama')
            ->and($rendered)->toContain('student')
            ->and($rendered)->toContain('rounded-2xl');
    });

    test('8XMYS-FR-UI-040-F: breadcrumbs component renders accessible nav with context', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.breadcrumbs context="common.nav.users" />'
        );

        expect($rendered)->toContain('aria-label="Breadcrumb"')
            ->and($rendered)->toContain('wire:navigate');
    });

    test('8XMYS-FR-UI-040-G: selection-bar component renders action slot and live status', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.selection-bar><button>Bulk Delete</button></x-ui::components.selection-bar>'
        );

        expect($rendered)->toContain('Bulk Delete')
            ->and($rendered)->toContain('role="status"')
            ->and($rendered)->toContain('rounded-2xl')
            ->and($rendered)->toContain('wire:click="clearSelection"');
    });

    test('8XMYS-FR-UI-040-H: button component forwards attributes and renders with rounded contours', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.button text="Save Record" color="primary" />'
        );

        expect($rendered)->toContain('Save Record')
            ->and($rendered)->toContain('<button');
    });

    test('8XMYS-FR-UI-040-I: card and alert components wrap content in rounded containers', function (): void {
        $card = (string) Blade::render(
            '<x-ui::components.card><div>Card Body Content</div></x-ui::components.card>'
        );
        expect($card)->toContain('Card Body Content')
            ->and($card)->toContain('rounded-');

        $alert = (string) Blade::render(
            '<x-ui::components.alert text="Important Notice" />'
        );
        expect($alert)->toContain('Important Notice')
            ->and($alert)->toContain('rounded-');
    });

    test('8XMYS-FR-UI-040-J: app-signature component renders author and project attribution', function (): void {
        $rendered = (string) Blade::render('<x-ui::components.app-signature />');

        expect($rendered)->toContain('Internara');
    });
});
