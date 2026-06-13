<?php

declare(strict_types=1);

use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\UserManagement\Actions\ArchiveStudentAccountsAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'student']);
});

test('archives student accounts from query', function () {
    $students = User::factory()->count(3)->create();
    $students->each(fn ($u) => $u->assignRole('student'));

    $query = User::role('student');
    $action = app(ArchiveStudentAccountsAction::class);
    $count = $action->execute($query);

    expect($count)->toBe(3);
    expect(User::where('status', AccountStatus::ARCHIVED->value)->count())->toBe(3);
});

test('skips super admin accounts in query', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $student = User::factory()->create();
    $student->assignRole('student');

    $query = User::whereNotNull('id');
    $action = app(ArchiveStudentAccountsAction::class);
    $count = $action->execute($query);

    expect($count)->toBe(1);
    expect($admin->fresh()->status)->not->toBe(AccountStatus::ARCHIVED);
});

test('processes users in chunks of 100', function () {
    User::factory()->count(150)->create()->each(fn ($u) => $u->assignRole('student'));

    $query = User::role('student');
    $action = app(ArchiveStudentAccountsAction::class);
    $count = $action->execute($query);

    expect($count)->toBe(150);
});

test('returns zero when query has no users', function () {
    $query = User::whereNull('id');
    $action = app(ArchiveStudentAccountsAction::class);
    $count = $action->execute($query);

    expect($count)->toBe(0);
});
