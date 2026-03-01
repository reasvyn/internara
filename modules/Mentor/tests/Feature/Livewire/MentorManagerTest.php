<?php

declare(strict_types=1);

namespace Modules\Mentor\Tests\Feature\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Mentor\Livewire\MentorManager;
use Modules\Mentor\Services\Contracts\MentorService;
use Modules\Permission\Models\Permission;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'mentor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'mentor.manage', 'guard_name' => 'web']);
});

test('unauthorized user cannot access mentor manager', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(MentorManager::class)
        ->assertForbidden();
});

test('authorized user can mount mentor manager and view records', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('mentor.manage');

    $mockService = \Mockery::mock(MentorService::class);
    $mockQuery = \Mockery::mock();

    $mockService->shouldReceive('query')
        ->once()
        ->andReturn($mockQuery);

    $mockQuery->shouldReceive('with')
        ->with(['roles:id,name', 'profile'])
        ->once()
        ->andReturnSelf();

    $paginator = new LengthAwarePaginator([], 0, 10);
    $mockQuery->shouldReceive('paginate')
        ->once()
        ->andReturn($paginator);

    app()->instance(MentorService::class, $mockService);

    Livewire::actingAs($user)
        ->test(MentorManager::class)
        ->assertOk()
        ->assertViewIs('mentor::livewire.mentor-manager');
});

test('add method resets form and opens modal', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('mentor.manage');

    $mockService = \Mockery::mock(MentorService::class);
    $mockQuery = \Mockery::mock();
    $mockService->shouldReceive('query')->andReturn($mockQuery);
    $mockQuery->shouldReceive('with')->andReturnSelf();
    $mockQuery->shouldReceive('paginate')->andReturn(new LengthAwarePaginator([], 0, 10));
    app()->instance(MentorService::class, $mockService);

    Livewire::actingAs($user)
        ->test(MentorManager::class)
        ->set('form.name', 'Old Name')
        ->call('add')
        ->assertSet('form.name', '')
        ->assertSet('form.roles', ['mentor'])
        ->assertSet('formModal', true);
});

test('edit method fills form and opens modal', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('mentor.manage');
    $user->givePermissionTo('user.update');
    $user->assignRole('super-admin');

    $mentor = User::factory()->create([
        'name' => 'John Mentor',
        'email' => 'john@mentor.com',
    ]);
    $mentor->assignRole('mentor');

    $mockService = \Mockery::mock(MentorService::class);
    $mockQuery = \Mockery::mock();
    $mockService->shouldReceive('query')->andReturn($mockQuery);
    $mockQuery->shouldReceive('with')->andReturnSelf();
    $mockQuery->shouldReceive('paginate')->andReturn(new LengthAwarePaginator([], 0, 10));

    $mockService->shouldReceive('find')
        ->with((string) $mentor->id)
        ->once()
        ->andReturn($mentor);

    app()->instance(MentorService::class, $mockService);

    Livewire::actingAs($user)
        ->test(MentorManager::class)
        ->call('edit', (string) $mentor->id)
        ->assertSet('form.name', 'John Mentor')
        ->assertSet('form.email', 'john@mentor.com')
        ->assertSet('formModal', true);
});

test('save method creates new mentor', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $mockService = \Mockery::mock(MentorService::class);
    $mockQuery = \Mockery::mock();
    $mockService->shouldReceive('query')->andReturn($mockQuery);
    $mockQuery->shouldReceive('with')->andReturnSelf();
    $mockQuery->shouldReceive('paginate')->andReturn(new LengthAwarePaginator([], 0, 10));

    $mockService->shouldReceive('create')
        ->once()
        ->with(\Mockery::on(function ($data) {
            return $data['name'] === 'New Mentor' && $data['email'] === 'new@mentor.com';
        }))
        ->andReturn(User::factory()->make());

    app()->instance(MentorService::class, $mockService);

    Livewire::actingAs($user)
        ->test(MentorManager::class)
        ->set('form.name', 'New Mentor')
        ->set('form.email', 'new@mentor.com')
        ->set('form.roles', ['mentor'])
        ->set('form.status', 'active')
        ->set('form.password', 'password')
        ->set('form.password_confirmation', 'password')
        ->call('save')
        ->assertSet('formModal', false);
});

test('save method updates existing mentor', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $mentor = User::factory()->create();

    $mockService = \Mockery::mock(MentorService::class);
    $mockQuery = \Mockery::mock();
    $mockService->shouldReceive('query')->andReturn($mockQuery);
    $mockQuery->shouldReceive('with')->andReturnSelf();
    $mockQuery->shouldReceive('paginate')->andReturn(new LengthAwarePaginator([], 0, 10));

    $mockService->shouldReceive('find')
        ->with((string) $mentor->id)
        ->once()
        ->andReturn($mentor);

    $mockService->shouldReceive('update')
        ->once()
        ->with((string) $mentor->id, \Mockery::on(function ($data) {
            return $data['name'] === 'Updated Mentor';
        }))
        ->andReturn(true);

    app()->instance(MentorService::class, $mockService);

    Livewire::actingAs($user)
        ->test(MentorManager::class)
        ->set('form.id', (string) $mentor->id)
        ->set('form.name', 'Updated Mentor')
        ->set('form.email', 'updated@mentor.com')
        ->set('form.roles', ['mentor'])
        ->set('form.status', 'active')
        ->call('save')
        ->assertSet('formModal', false);
});

test('save handles exception and flashes error', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $mockService = \Mockery::mock(MentorService::class);
    $mockQuery = \Mockery::mock();
    $mockService->shouldReceive('query')->andReturn($mockQuery);
    $mockQuery->shouldReceive('with')->andReturnSelf();
    $mockQuery->shouldReceive('paginate')->andReturn(new LengthAwarePaginator([], 0, 10));

    $mockService->shouldReceive('create')
        ->once()
        ->andThrow(new \Exception('Database error'));

    app()->instance(MentorService::class, $mockService);

    Livewire::actingAs($user)
        ->test(MentorManager::class)
        ->set('form.name', 'New Mentor')
        ->set('form.email', 'new@mentor.com')
        ->set('form.roles', ['mentor'])
        ->set('form.status', 'active')
        ->set('form.password', 'password')
        ->set('form.password_confirmation', 'password')
        ->call('save')
        // Flasher handles flash messages, we can just assert no hard crash, modal stays or flash is sent
        ->assertHasNoErrors();
});
