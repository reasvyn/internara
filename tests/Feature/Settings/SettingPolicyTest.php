<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Settings\Models\Setting;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('SettingPolicy', function () {
    test('allows admin on viewAny, view, and update; denies student', function () {
        $setting = Setting::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('viewAny', Setting::class))->toBeTrue()
            ->and(Gate::allows('view', $setting))->toBeTrue()
            ->and(Gate::allows('update', Setting::class))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', Setting::class))->toBeFalse()
            ->and(Gate::allows('view', $setting))->toBeFalse()
            ->and(Gate::allows('update', Setting::class))->toBeFalse();
    });

    test('reserves create and delete to super_admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', Setting::class))->toBeFalse()
            ->and(Gate::allows('delete', Setting::class))->toBeFalse();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Setting::class))->toBeFalse()
            ->and(Gate::allows('delete', Setting::class))->toBeFalse();
    });

    test('allows superadmin on create and delete via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin);

        expect(Gate::allows('create', Setting::class))->toBeTrue()
            ->and(Gate::allows('delete', Setting::class))->toBeTrue();
    });
});
