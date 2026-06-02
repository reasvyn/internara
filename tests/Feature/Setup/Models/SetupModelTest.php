<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Models;

use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\Setup\Entities\SetupState;
use App\Domain\Setup\Models\Setup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
});

describe('Setup model', function () {
    it('uses HasFactory trait', function () {
        expect(in_array(HasFactory::class, class_uses(Setup::class)))->toBeTrue();
    });

    it('factory creates a setup record', function () {
        $setup = Setup::factory()->create();

        expect($setup->exists)->toBeTrue()
            ->and($setup->is_installed)->toBeFalse()
            ->and($setup->completed_steps)->toBe([])
            ->and($setup->setup_token)->toBeNull();
    });

    it('factory installed state', function () {
        $setup = Setup::factory()->installed()->create();

        expect($setup->is_installed)->toBeTrue();
    });

    it('factory with token state', function () {
        $setup = Setup::factory()->withToken()->create();

        expect($setup->setup_token)->not->toBeNull()
            ->and($setup->token_expires_at)->not->toBeNull();
    });

    it('factory with recovery key state', function () {
        $setup = Setup::factory()->withRecoveryKey()->create();

        expect($setup->recovery_key)->not->toBeNull();
    });

    it('casts attributes correctly', function () {
        $setup = Setup::factory()->create([
            'is_installed' => true,
            'completed_steps' => ['school', 'department'],
            'token_expires_at' => now(),
        ]);

        expect($setup->is_installed)->toBeTrue()
            ->and($setup->completed_steps)->toBe(['school', 'department'])
            ->and($setup->token_expires_at)->toBeInstanceOf(Carbon::class);
    });

    it('converts to SetupState via asSetupState', function () {
        $setup = Setup::factory()->create([
            'is_installed' => true,
            'setup_token' => 'encrypted',
            'token_expires_at' => now()->addHour(),
            'completed_steps' => ['school'],
            'recovery_key' => 'hashed',
        ]);

        $state = $setup->asSetupState();

        expect($state)->toBeInstanceOf(SetupState::class)
            ->and($state->isInstalled())->toBeTrue()
            ->and($state->hasStoredToken())->toBeTrue()
            ->and($state->isStepCompleted('school'))->toBeTrue()
            ->and($state->hasRecoveryKey())->toBeTrue();
    });

    it('creates SetupState via static state method', function () {
        Setup::factory()->create(['is_installed' => false]);

        $state = Setup::state();

        expect($state)->toBeInstanceOf(SetupState::class)
            ->and($state->isInstalled())->toBeFalse();
    });

    it('state method sets is_installed from DB', function () {
        Setup::factory()->installed()->create();

        expect(Setup::state()->isInstalled())->toBeTrue();
    });

    it('creates record with is_installed from factory override', function () {
        $setup = Setup::factory()->create(['is_installed' => true]);

        expect($setup->is_installed)->toBeTrue();
    });

    it('fromModel correctly maps installed', function () {
        $model = new Setup;
        $model->is_installed = true;
        $model->setup_token = null;
        $model->token_expires_at = null;
        $model->completed_steps = [];
        $model->recovery_key = null;

        $state = SetupState::fromModel($model);

        expect($state->isInstalled())->toBeTrue();
    });

    it('has school relationship', function () {
        $school = School::factory()->create();
        $setup = Setup::factory()->create(['school_id' => $school->id]);

        expect($setup->school)->toBeInstanceOf(School::class)
            ->and($setup->school->id)->toBe($school->id);
    });

    it('has department relationship', function () {
        $department = Department::factory()->create();
        $setup = Setup::factory()->create(['department_id' => $department->id]);

        expect($setup->department)->toBeInstanceOf(Department::class)
            ->and($setup->department->id)->toBe($department->id);
    });

    it('has uuid primary key', function () {
        $setup = Setup::factory()->create();

        expect($setup->getKeyType())->toBe('string');
    });
});
