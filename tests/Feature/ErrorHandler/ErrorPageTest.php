<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

describe('403 Forbidden', function () {
    it('returns 403 when accessing admin route with insufficient role', function () {
        $user = User::factory()->create()->assignRole('student');
        $this->actingAs($user);

        $this->get('/admin/school')
            ->assertStatus(403);
    });

    it('shows the custom middleware message on 403 page', function () {
        $user = User::factory()->create()->assignRole('student');
        $this->actingAs($user);

        $this->get('/admin/school')
            ->assertStatus(403)
            ->assertSee('clearance level');
    });
});

describe('404 Not Found', function () {
    it('returns 404 for non-existent route', function () {
        $this->get('/this-route-does-not-exist')
            ->assertStatus(404)
            ->assertSee(__('Not Found'));
    });

    it('returns 404 for non-existent named route', function () {
        $this->get('/admin/users/nonexistent')
            ->assertStatus(404)
            ->assertSee(__('Not Found'));
    });
});

describe('503 Service Unavailable', function () {
    it('returns 503 when application is in maintenance mode', function () {
        Artisan::call('down');

        $this->get('/login')
            ->assertStatus(503)
            ->assertSee(__('Service Unavailable'));

        Artisan::call('up');
    });

    it('returns normal response after maintenance mode ends', function () {
        $this->get('/login')
            ->assertStatus(200);
    });
});
