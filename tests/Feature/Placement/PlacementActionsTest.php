<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Internship\Models\Internship;
use App\Domain\Partnership\Models\Company;
use App\Domain\Placement\Actions\ApprovePlacementChangeAction;
use App\Domain\Placement\Actions\CreatePlacementAction;
use App\Domain\Placement\Actions\DeletePlacementAction;
use App\Domain\Placement\Actions\DirectPlacementAction;
use App\Domain\Placement\Actions\RejectPlacementChangeAction;
use App\Domain\Placement\Actions\RequestPlacementChangeAction;
use App\Domain\Placement\Actions\UpdatePlacementAction;
use App\Domain\Placement\Models\Placement;
use App\Domain\Placement\Models\PlacementChangeRequest;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
});

describe('CreatePlacementAction', function () {
    it('creates a placement', function () {
        $company = Company::factory()->create();
        $internship = Internship::factory()->create();

        $placement = app(CreatePlacementAction::class)->execute([
            'company_id' => $company->id,
            'internship_id' => $internship->id,
            'name' => 'Software Developer Intern',
            'quota' => 10,
        ]);

        expect($placement)->toBeInstanceOf(Placement::class)
            ->and($placement->filled_quota)->toBe(0);
    });
});

describe('UpdatePlacementAction', function () {
    it('updates a placement', function () {
        $placement = Placement::factory()->create();

        $updated = app(UpdatePlacementAction::class)->execute($placement, [
            'name' => 'Updated Placement',
            'quota' => 20,
        ]);

        expect($updated->name)->toBe('Updated Placement')
            ->and($updated->quota)->toBe(20);
    });
});

describe('DeletePlacementAction', function () {
    it('deletes a placement with no active registrations', function () {
        $placement = Placement::factory()->create();

        app(DeletePlacementAction::class)->execute($placement);

        expect(Placement::find($placement->id))->toBeNull();
    });
});

describe('DirectPlacementAction', function () {
    it('directly places a student', function () {
        $student = User::factory()->create();
        $placement = Placement::factory()->create(['filled_quota' => 0]);

        $registration = app(DirectPlacementAction::class)->execute($student, [
            'placement_id' => $placement->id,
        ]);

        expect($registration)->toBeInstanceOf(Registration::class)
            ->and($registration->hasStatus('active'))->toBeTrue()
            ->and($placement->fresh()->filled_quota)->toBe(1);
    });
});

describe('RequestPlacementChangeAction', function () {
    it('creates a placement change request', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $currentPlacement = Placement::factory()->create([
            'internship_id' => $internship->id,
        ]);
        $registration = Registration::factory()->create([
            'internship_id' => $internship->id,
            'placement_id' => $currentPlacement->id,
        ]);
        $targetPlacement = Placement::factory()->create([
            'internship_id' => $internship->id,
        ]);

        $request = app(RequestPlacementChangeAction::class)->execute($registration, [
            'to_placement_id' => $targetPlacement->id,
            'reason' => 'Better opportunity',
            'requested_by' => $admin->id,
        ]);

        expect($request)->toBeInstanceOf(PlacementChangeRequest::class)
            ->and($request->status->value)->toBe('pending');
    });
});

describe('ApprovePlacementChangeAction', function () {
    it('approves a placement change request', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        $internship = Internship::factory()->create();
        $registration = Registration::factory()->create([
            'internship_id' => $internship->id,
        ]);
        $oldPlacement = Placement::factory()->create([
            'internship_id' => $internship->id,
            'filled_quota' => 1,
        ]);
        $newPlacement = Placement::factory()->create([
            'internship_id' => $internship->id,
            'filled_quota' => 0,
        ]);
        $registration->update(['placement_id' => $oldPlacement->id]);

        $requestId = (string) Str::uuid();
        DB::table('placement_change_requests')->insert([
            'id' => $requestId,
            'registration_id' => $registration->id,
            'from_placement_id' => $oldPlacement->id,
            'to_placement_id' => $newPlacement->id,
            'reason' => 'Better opportunity',
            'requested_by' => $admin->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = PlacementChangeRequest::find($requestId);

        app(ApprovePlacementChangeAction::class)->execute($request);

        expect($request->fresh()->status->value)->toBe('approved')
            ->and($oldPlacement->fresh()->filled_quota)->toBe(0)
            ->and($newPlacement->fresh()->filled_quota)->toBe(1);
    });
});

describe('RejectPlacementChangeAction', function () {
    it('rejects a placement change request', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        $requestId = (string) Str::uuid();
        DB::table('placement_change_requests')->insert([
            'id' => $requestId,
            'registration_id' => Registration::factory()->create()->id,
            'from_placement_id' => Placement::factory()->create()->id,
            'to_placement_id' => Placement::factory()->create()->id,
            'reason' => 'Change requested',
            'requested_by' => $admin->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = PlacementChangeRequest::find($requestId);

        app(RejectPlacementChangeAction::class)->execute($request, 'Quota unavailable');

        expect($request->fresh()->status->value)->toBe('rejected')
            ->and($request->fresh()->rejection_reason)->toBe('Quota unavailable');
    });
});
