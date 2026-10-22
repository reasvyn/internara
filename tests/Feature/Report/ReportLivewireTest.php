<?php

declare(strict_types=1);

namespace Tests\Feature\Report;

use App\Modules\Report\Domain\StudentReport\Actions\CreateStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Livewire\StudentReportsManager;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('R6BMW: Report Livewire UI Components', function (): void {
    test('R6BMW-FR-REP-001: StudentReportsManager renders report list and computes registrations', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $test = Livewire::test(StudentReportsManager::class);

        $test->assertOk()
            ->assertSet('createModal', false)
            ->assertSet('calculateModal', false)
            ->assertSet('finalizeModal', false);

        expect($test->instance()->registrations)->not->toBeNull()
            ->and($test->instance()->reports)->not->toBeNull();
    });

    test('R6BMW-FR-REP-002: StudentReportsManager manages modal opening and parameter assignment', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $test = Livewire::test(StudentReportsManager::class);

        $test->call('openCreateModal');
        expect($test->get('createModal'))->toBeTrue()
            ->and($test->get('selectedRegistrationId'))->toBeNull();

        $test->call('openCalculateModal', 'report-uuid-001');
        expect($test->get('calculateModal'))->toBeTrue()
            ->and($test->get('calculateReportId'))->toBe('report-uuid-001');

        $test->call('openFinalizeModal', 'report-uuid-002');
        expect($test->get('finalizeModal'))->toBeTrue()
            ->and($test->get('finalizeReportId'))->toBe('report-uuid-002');
    });

    test('R6BMW-FR-REP-003: createReport validates selectedRegistrationId is required and valid', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(StudentReportsManager::class)
            ->call('createReport', app(CreateStudentReportAction::class))
            ->assertHasErrors(['selectedRegistrationId' => 'required']);
    });
});
