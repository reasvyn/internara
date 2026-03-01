<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Services;

use Illuminate\Support\Facades\DB;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\User\Models\User;

describe('Partner Quota Management Deep Audit (BP-PLC-01)', function () {
    beforeEach(function () {
        $this->seed(\Modules\Permission\Database\Seeders\PermissionDatabaseSeeder::class);
        $this->registrationService = app(RegistrationService::class);
        $this->placementService = app(InternshipPlacementService::class);

        // Setup base data
        $this->program = Internship::factory()->create();
        $this->teacher = User::factory()->create();

        // Authenticate as SuperAdmin
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->actingAs($this->admin);

        setting(['system_phase' => 'registration']);
    });

    test('it uses database transactions for registration', function () {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturn(new \Modules\Internship\Models\InternshipRegistration);

        $data = [
            'internship_id' => $this->program->id,
            'placement_id' => InternshipPlacement::factory()->create()->id,
            'student_id' => User::factory()->create()->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ];

        $this->registrationService->register($data);
    });

    test(
        'quota release audit: cancelling or deleting a registration restores the slot',
        function () {
            $placement = InternshipPlacement::factory()->create([
                'internship_id' => $this->program->id,
                'capacity_quota' => 1,
            ]);

            $student = User::factory()->create();
            $data = [
                'internship_id' => $this->program->id,
                'placement_id' => $placement->id,
                'student_id' => $student->id,
                'teacher_id' => $this->teacher->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
            ];

            // 1. Occupy the only slot
            $registration = $this->registrationService->register($data);
            $registration->setStatus('active');

            expect($placement->refresh()->remainingSlots)->toBe(0);

            // 2. Cancel/Deactivate the registration
            $registration->setStatus('inactive');

            // 3. Quota should be restored
            expect($placement->refresh()->remainingSlots)->toBe(1);
        },
    );
});
