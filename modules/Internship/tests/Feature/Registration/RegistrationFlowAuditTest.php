<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Registration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Internship\Events\InternshipRegistered;
use Modules\Internship\Services\Contracts\InternshipRequirementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\User\Services\Contracts\UserService;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Modules\Permission\Models\Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);
    $admin = \Modules\User\Models\User::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin);
});

test(
    'requirement guard audit: it rejects approval if mandatory requirements are missing',
    function () {
        $registration = \Modules\Internship\Models\InternshipRegistration::factory()->create();

        // Mock requirement service to return false
        $mock = $this->mock(InternshipRequirementService::class);
        $mock->shouldReceive('hasClearedMandatory')->once()->andReturn(false);

        expect(fn () => app(RegistrationService::class)->approve($registration->id))->toThrow(
            \Modules\Exception\AppException::class,
            'internship::exceptions.mandatory_requirements_not_met',
        );
    },
);

test(
    'event governance audit: it dispatches InternshipRegistered with lightweight payload',
    function () {
        Event::fake();

        $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
            ->factory()
            ->create();
        $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
            ->factory()
            ->create(['internship_id' => $program->id]);
        $student = app(UserService::class)->factory()->create();
        $teacher = app(UserService::class)->factory()->create();

        $registration = app(RegistrationService::class)->register([
            'internship_id' => $program->id,
            'placement_id' => $placement->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);

        Event::assertDispatched(function (InternshipRegistered $event) {
            // Blueprint Mandate: Must ONLY carry the UUID
            $reflect = new \ReflectionClass($event);
            $props = $reflect->getProperties();

            return count($props) === 1 && $props[0]->getName() === 'registrationId';
        });
    },
);
