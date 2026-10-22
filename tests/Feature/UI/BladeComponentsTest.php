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

    test('8XMYS-FR-UI-040-K: page-header component renders title, description, and action slots', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.page-header title="Student Management" description="Manage all enrolled student accounts">
                <x-slot:actions><button>Add Student</button></x-slot:actions>
            </x-ui::components.page-header>'
        );

        expect($rendered)->toContain('Student Management')
            ->and($rendered)->toContain('Manage all enrolled student accounts')
            ->and($rendered)->toContain('Add Student');
    });

    test('8XMYS-FR-UI-040-L: badge component renders with rounded pill contour', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.badge text="Active" color="green" />'
        );

        expect($rendered)->toContain('Active')
            ->and($rendered)->toContain('rounded-full');
    });

    test('8XMYS-FR-UI-040-M: avatar component renders user initials when avatar media is absent', function (): void {
        $user = User::factory()->create(['name' => 'Budi Santoso']);

        $rendered = (string) Blade::render(
            '<x-ui::components.avatar :user="$user" />',
            ['user' => $user]
        );

        expect($rendered)->toContain('rounded-full')
            ->and($rendered)->toContain($user->initials());
    });

    test('8XMYS-FR-UI-040-N: brand component renders brand name and logo image with size variants', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.brand size="lg" :withTagline="true" />'
        );

        expect($rendered)->toContain('size-14')
            ->and($rendered)->toContain('<img')
            ->and($rendered)->toContain(brand('name'));
    });

    test('8XMYS-FR-UI-040-O: display-field component renders label, value, and icon with rounded container', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::components.display-field label="NISN" value="1234567890" icon="identification" />'
        );

        expect($rendered)->toContain('NISN')
            ->and($rendered)->toContain('1234567890')
            ->and($rendered)->toContain('rounded-xl')
            ->and($rendered)->toContain('<svg');
    });

    test('8XMYS-FR-UI-040-P: confirm partial dialog contains modal structure and action bindings', function (): void {
        $viewContent = file_get_contents(resource_path('views/ui/components/confirm.blade.php'));

        expect($viewContent)->toContain('wire="showConfirm"')
            ->and($viewContent)->toContain('confirmAction')
            ->and($viewContent)->toContain('rounded-2xl');
    });

    test('8XMYS-FR-UI-040-Q: action-button widget renders quick tile with icon and navigation link', function (): void {
        $rendered = (string) Blade::render(
            '<x-ui::widgets.action-button label="Submit Report" icon="document-text" link="/reports/submit" color="primary" />'
        );

        expect($rendered)->toContain('Submit Report')
            ->and($rendered)->toContain('href="/reports/submit"')
            ->and($rendered)->toContain('rounded-2xl')
            ->and($rendered)->toContain('wire:navigate');
    });

    test('8XMYS-FR-UI-040-R: form input wrappers render with canonical rounded contours', function (): void {
        $input = (string) Blade::render(
            '<x-ui::components.input label="Full Name" />'
        );
        expect($input)->toContain('Full Name')
            ->and($input)->toContain('rounded-xl');

        $textarea = (string) Blade::render(
            '<x-ui::components.textarea label="Detailed Notes" rows="4" />'
        );
        expect($textarea)->toContain('Detailed Notes')
            ->and($textarea)->toContain('rounded-xl');

        $checkbox = (string) Blade::render(
            '<x-ui::components.checkbox label="Accept Terms" />'
        );
        expect($checkbox)->toContain('Accept Terms')
            ->and($checkbox)->toContain('rounded-md');

        $tooltip = (string) Blade::render(
            '<x-ui::components.tooltip text="Help info" />'
        );
        expect($tooltip)->toContain('Help info')
            ->and($tooltip)->toContain('rounded-xl');
    });

    test('8XMYS-FR-UI-040-S: table component renders modern overflow-x-auto container with rounded contour', function (): void {
        $table = (string) Blade::render(
            '<x-ui::components.table :headers="[[\'index\' => \'id\', \'label\' => \'ID\']]" :rows="collect([])" />'
        );
        expect($table)->toContain('overflow-x-auto')
            ->and($table)->toContain('rounded-2xl');
    });
});
