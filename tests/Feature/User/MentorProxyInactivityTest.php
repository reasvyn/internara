<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setting\Services\Settings;
use App\Modules\User\Domain\Mentor\Entities\MentorEntity;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('T4B26: supervisor inactivity window for cross-role proxy', function (): void {
    test('T4B26-FR-CRP4, T4B26-FR-RBAC-019: registration asMentorEntity resolves supervisor inactivity window from activity log and settings', function (): void {
        Setting::updateOrCreate(
            ['key' => 'journals.proxy_inactivity_hours'],
            ['value' => 48, 'type' => 'integer', 'group' => 'operational']
        );
        Settings::forget('journals.proxy_inactivity_hours');

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $registration = Registration::factory()->create();
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $teacher->id,
        ]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $supervisor->id,
        ]);

        // When supervisor has no activity logs, teacher can proxy
        $entity = $registration->asMentorEntity();
        expect($entity)->toBeInstanceOf(MentorEntity::class);
        expect($entity->canProxyAsSupervisor($teacher))->toBeTrue();

        // Log an activity for the supervisor 2 hours ago (within 48h)
        activity()
            ->causedBy($supervisor)
            ->performedOn($registration)
            ->createdAt(Carbon::now()->subHours(2))
            ->log('supervisor-recent-activity');

        $entityRecent = $registration->asMentorEntity();
        expect($entityRecent->canProxyAsSupervisor($teacher))->toBeFalse();
        expect($entityRecent->canVerifyLogbook($teacher))->toBeFalse();

        // Update the activity created_at to 55 hours ago (> 48h)
        Activity::where('causer_id', $supervisor->id)->update(['created_at' => Carbon::now()->subHours(55)]);

        $entityInactive = $registration->asMentorEntity();
        expect($entityInactive->canProxyAsSupervisor($teacher))->toBeTrue();
        expect($entityInactive->canVerifyLogbook($teacher))->toBeTrue();

        // Update the setting to 72 hours inactivity window
        Setting::updateOrCreate(
            ['key' => 'journals.proxy_inactivity_hours'],
            ['value' => 72, 'type' => 'integer', 'group' => 'operational']
        );
        Settings::forget('journals.proxy_inactivity_hours');

        // Now 55 hours is within the 72h window, so teacher proxy should be refused
        $entityLongerWindow = $registration->asMentorEntity();
        expect($entityLongerWindow->inactivityHours())->toBe(72);
        expect($entityLongerWindow->canProxyAsSupervisor($teacher))->toBeFalse();
    });
});
