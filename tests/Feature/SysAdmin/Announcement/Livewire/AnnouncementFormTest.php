<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Livewire\AnnouncementManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders within announcement manager', function () {
    Livewire::test(AnnouncementManager::class)
        ->assertSuccessful();
});

test('validates title is required via form', function () {
    Livewire::test(AnnouncementManager::class)
        ->set('form.title', '')
        ->set('form.message', 'Test message')
        ->set('form.type', 'info')
        ->call('save')
        ->assertHasErrors(['form.title']);
});

test('validates message is required via form', function () {
    Livewire::test(AnnouncementManager::class)
        ->set('form.title', 'Test')
        ->set('form.message', '')
        ->set('form.type', 'info')
        ->call('save')
        ->assertHasErrors(['form.message']);
});
