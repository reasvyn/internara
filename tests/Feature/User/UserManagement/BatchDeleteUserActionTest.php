<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\UserManagement\Actions\BatchDeleteUserAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'student']);
    $this->action = app(BatchDeleteUserAction::class);
});

test('deletes multiple non-admin users', function () {
    $users = User::factory()->count(3)->create();
    $users->each(fn ($u) => $u->assignRole('student'));
    $ids = $users->pluck('id')->toArray();

    $result = $this->action->execute($ids);

    expect($result)->toMatchArray(['deleted' => 3, 'skipped' => 0]);
});

test('skips super admin users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $student = User::factory()->create();
    $student->assignRole('student');

    $result = $this->action->execute([$admin->id, $student->id]);

    expect($result)->toMatchArray(['deleted' => 1, 'skipped' => 1]);
});

test('skips own account', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $this->actingAs($user);

    $result = $this->action->execute([$user->id]);

    expect($result)->toMatchArray(['deleted' => 0, 'skipped' => 1]);
});

test('skips non-existent user ids', function () {
    $result = $this->action->execute(['non-existent-id']);

    expect($result)->toMatchArray(['deleted' => 0, 'skipped' => 1]);
});

test('returns empty result for empty ids array', function () {
    $result = $this->action->execute([]);

    expect($result)->toMatchArray(['deleted' => 0, 'skipped' => 0]);
});
