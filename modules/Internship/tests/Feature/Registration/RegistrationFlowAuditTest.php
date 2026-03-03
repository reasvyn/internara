<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Registration;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Internship\Events\InternshipRegistered;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipRequirementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\User\Models\User;

beforeEach(function () {
    Gate::before(function ($user, $ability) {
        return $user->hasRole('super-admin') ? true : null;
    });

    \Modules\Permission\Models\Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);
    
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin);
});

test(
    'requirement guard audit: it rejects approval if mandatory requirements are missing',
    function () {
        $registration = InternshipRegistration::factory()->create();

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

        $program = Internship::factory()->create();
        $placement = InternshipPlacement::factory()->create(['internship_id' => $program->id]);
        $student = User::factory()->create();
        $teacher = User::factory()->create();

        try {
            $registration = app(RegistrationService::class)->register([
                'internship_id' => $program->id,
                'placement_id' => $placement->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
            ]);
        } catch (\Exception $e) {
            dump($e->getMessage());
            if ($e->getPrevious()) {
                dump($e->getPrevious()->getMessage());
            }
            throw $e;
        }

        Event::assertDispatched(function (InternshipRegistered $event) {
            // Blueprint Mandate: Must ONLY carry the UUID
            $reflect = new \ReflectionClass($event);
            $props = $reflect->getProperties();

            return count($props) === 1 && $props[0]->getName() === 'registrationId';
        });
    },
);
