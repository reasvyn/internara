<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Internship\Models\Internship;
use App\Domain\Placement\Models\Placement;
use App\Domain\Registration\Actions\ApplyAccountAction;
use App\Domain\Registration\Actions\ApproveAccountApplicationAction;
use App\Domain\Registration\Actions\RegisterInternshipAction;
use App\Domain\Registration\Actions\RejectAccountApplicationAction;
use App\Domain\Registration\Actions\VerifyRegistrationAction;
use App\Domain\Registration\Models\AccountApplication;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
});

describe('ApplyAccountAction', function () {
    it('creates an account application', function () {
        $internship = Internship::factory()->create();

        $application = app(ApplyAccountAction::class)->execute([
            'name' => 'John Applicant',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'internship_id' => $internship->id,
        ]);

        expect($application)->toBeInstanceOf(AccountApplication::class)
            ->and($application->status->value)->toBe('pending');
    });

    it('throws for duplicate email', function () {
        $internship = Internship::factory()->create();
        AccountApplication::factory()->create([
            'email' => 'dup@example.com',
            'status' => 'pending',
            'internship_id' => $internship->id,
        ]);

        app(ApplyAccountAction::class)->execute([
            'name' => 'Dup',
            'email' => 'dup@example.com',
            'internship_id' => Internship::factory()->create()->id,
        ]);
    })->throws(RejectedException::class);
});

describe('ApproveAccountApplicationAction', function () {
    it('approves a pending application and creates registration', function () {
        $internship = Internship::factory()->create();
        $placement = Placement::factory()->create(['internship_id' => $internship->id]);
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);

        $applicationId = (string) Str::uuid();
        DB::table('account_applications')->insert([
            'id' => $applicationId,
            'name' => 'Test User',
            'email' => 'test@app.com',
            'phone' => '081111',
            'status' => 'pending',
            'internship_id' => $internship->id,
            'placement_id' => $placement->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $registration = app(ApproveAccountApplicationAction::class)->execute($applicationId, $admin);

        expect($registration)->toBeInstanceOf(Registration::class)
            ->and($registration->hasStatus('active'))->toBeTrue();
    });
});

describe('RejectAccountApplicationAction', function () {
    it('rejects a pending application', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);

        $applicationId = (string) Str::uuid();
        DB::table('account_applications')->insert([
            'id' => $applicationId,
            'name' => 'Test User',
            'email' => 'test@reject.com',
            'status' => 'pending',
            'internship_id' => Internship::factory()->create()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(RejectAccountApplicationAction::class)->execute($applicationId, $admin, 'Incomplete documents');

        $application = AccountApplication::find($applicationId);
        expect($application->status->value)->toBe('rejected')
            ->and($application->rejection_reason)->toBe('Incomplete documents');
    });
});

describe('RegisterInternshipAction', function () {
    it('registers a student for internship', function () {
        $student = User::factory()->create();
        $student->assignRole(Role::STUDENT->value);
        $internship = Internship::factory()->create();

        $registration = app(RegisterInternshipAction::class)->execute($student, [
            'internship_id' => $internship->id,
        ]);

        expect($registration)->toBeInstanceOf(Registration::class)
            ->and($registration->hasStatus('pending'))->toBeTrue();
    });
});

describe('VerifyRegistrationAction', function () {
    it('verifies and places a pending registration', function () {
        $registration = Registration::factory()->create();
        $registration->setStatus('pending', 'test');
        $placement = Placement::factory()->create(['filled_quota' => 0]);

        $verified = app(VerifyRegistrationAction::class)->execute($registration->id, [
            'placement_id' => $placement->id,
        ]);

        expect($verified->hasStatus('active'))->toBeTrue()
            ->and($placement->fresh()->filled_quota)->toBe(1);
    });
});
