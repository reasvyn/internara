<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Actions\DeleteAnnouncementAction;
use App\SysAdmin\Announcement\Actions\PublishAnnouncementAction;
use App\SysAdmin\Announcement\Actions\SendAnnouncementAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Livewire\AnnouncementManager;
use App\SysAdmin\Announcement\Models\Announcement;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the announcement manager component', function () {
    Livewire::test(AnnouncementManager::class)
        ->assertSuccessful();
});

test('sends a new announcement as draft', function () {
    $this->mock(SendAnnouncementAction::class)
        ->shouldReceive('execute')
        ->once();

    Livewire::test(AnnouncementManager::class)
        ->set('form.title', 'Test')
        ->set('form.message', 'Test message')
        ->set('form.type', 'info')
        ->call('save')
        ->assertSet('showForm', false);
});

test('confirm delete opens confirmation modal', function () {
    $announcement = Announcement::factory()->create([
        'created_by' => auth()->id(),
    ]);

    Livewire::test(AnnouncementManager::class)
        ->call('confirmDelete', $announcement->id)
        ->assertSet('confirmId', $announcement->id)
        ->assertSet('confirmActionType', 'delete')
        ->assertSet('showConfirm', true);
});

test('confirm publish opens confirmation modal', function () {
    $announcement = Announcement::factory()->create([
        'created_by' => auth()->id(),
        'status' => AnnouncementStatus::DRAFT,
    ]);

    Livewire::test(AnnouncementManager::class)
        ->call('confirmPublish', $announcement->id)
        ->assertSet('confirmId', $announcement->id)
        ->assertSet('confirmActionType', 'publish')
        ->assertSet('showConfirm', true);
});

test('deletes announcement after confirmation', function () {
    $announcement = Announcement::factory()->create([
        'created_by' => auth()->id(),
    ]);

    $this->mock(DeleteAnnouncementAction::class)
        ->shouldReceive('execute')
        ->once();

    Livewire::test(AnnouncementManager::class)
        ->call('confirmDelete', $announcement->id)
        ->call('confirmAction')
        ->assertSet('showConfirm', false);
});

test('publishes announcement after confirmation', function () {
    $announcement = Announcement::factory()->create([
        'created_by' => auth()->id(),
        'status' => AnnouncementStatus::DRAFT,
    ]);

    $this->mock(PublishAnnouncementAction::class)
        ->shouldReceive('execute')
        ->once();

    Livewire::test(AnnouncementManager::class)
        ->call('confirmPublish', $announcement->id)
        ->call('confirmAction')
        ->assertSet('showConfirm', false);
});

test('reset form clears state', function () {
    Livewire::test(AnnouncementManager::class)
        ->set('showForm', true)
        ->call('resetForm')
        ->assertSet('showForm', false);
});
