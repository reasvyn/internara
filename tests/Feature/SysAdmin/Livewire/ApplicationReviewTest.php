<?php

declare(strict_types=1);

use App\Enrollment\AccountApplication\Models\AccountApplication;
use App\Enrollment\AccountApplication\Actions\ApproveAccountApplicationAction;
use App\Enrollment\AccountApplication\Actions\RejectAccountApplicationAction;
use App\SysAdmin\Livewire\ApplicationReview;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the application review component', function () {
    Livewire::test(ApplicationReview::class)
        ->assertSuccessful();
});

test('shows empty state when no pending applications', function () {
    Livewire::test(ApplicationReview::class)
        ->assertSee(__('internship.applications.empty'));
});

test('displays pending applications', function () {
    AccountApplication::factory()->create(['status' => 'pending']);

    Livewire::test(ApplicationReview::class)
        ->assertSet('pendingApplications', function ($apps) {
            return $apps->isNotEmpty();
        });
});

test('approves an application', function () {
    $application = AccountApplication::factory()->create(['status' => 'pending']);

    $this->mock(ApproveAccountApplicationAction::class)
        ->shouldReceive('execute')
        ->once()
        ->with($application->id, Mockery::type(User::class));

    Livewire::test(ApplicationReview::class)
        ->call('approve', $application->id);
});

test('confirm reject opens the reject modal', function () {
    $application = AccountApplication::factory()->create(['status' => 'pending']);

    Livewire::test(ApplicationReview::class)
        ->call('confirmReject', $application->id)
        ->assertSet('rejectId', $application->id)
        ->assertSet('showRejectModal', true)
        ->assertSet('rejectionReason', '');
});

test('rejects an application with reason', function () {
    $application = AccountApplication::factory()->create(['status' => 'pending']);

    $this->mock(RejectAccountApplicationAction::class)
        ->shouldReceive('execute')
        ->once()
        ->with($application->id, Mockery::type(User::class), 'Not qualified');

    Livewire::test(ApplicationReview::class)
        ->call('confirmReject', $application->id)
        ->set('rejectionReason', 'Not qualified')
        ->call('reject')
        ->assertSet('showRejectModal', false)
        ->assertSet('rejectId', null);
});
