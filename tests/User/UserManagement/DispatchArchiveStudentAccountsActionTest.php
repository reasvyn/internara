<?php

declare(strict_types=1);

use App\Modules\User\Domain\UserManagement\Actions\DispatchArchiveStudentAccountsAction;
use App\Modules\User\Domain\UserManagement\Livewire\StudentManager;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Jobs\ArchiveStudentAccountsJob;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Event::fake();
});

function studentQuery(): Builder
{
    return User::query()->role('student');
}

function queuedStudentIds(ArchiveStudentAccountsJob $job): array
{
    return collect(
        Closure::bind(fn () => $this->studentIds, $job, ArchiveStudentAccountsJob::class)()
    )->sort()->values()->all();
}

describe('E1MSJ: DispatchArchiveStudentAccountsAction', function () {
    test('E1MSJ-UC-3/FR-AS1: below threshold archives synchronously without queueing', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');
        $students = User::factory()->count(2)->create(['status' => AccountStatus::VERIFIED->value]);
        $students->each(fn ($u) => $u->assignRole('student'));

        $this->actingAs($admin);
        Queue::fake();

        $count = app(DispatchArchiveStudentAccountsAction::class)
            ->execute(studentQuery(), queueThreshold: 3);

        expect($count)->toBe(2)
            ->and(User::find($students[0]->id)->status)->toBe(AccountStatus::ARCHIVED)
            ->and(User::find($students[1]->id)->status)->toBe(AccountStatus::ARCHIVED);

        Queue::assertNothingPushed();
    });

    test('E1MSJ-NFR-R2/FR-AS5: above threshold dispatches queued job with student ids', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');
        $students = User::factory()->count(4)->create(['status' => AccountStatus::VERIFIED->value]);
        $students->each(fn ($u) => $u->assignRole('student'));
        $expectedIds = $students->map(fn ($u) => (string) $u->id)->sort()->values()->all();

        $this->actingAs($admin);
        Queue::fake();

        $count = app(DispatchArchiveStudentAccountsAction::class)
            ->execute(studentQuery(), queueThreshold: 2);

        expect($count)->toBe(4);

        Queue::assertPushed(function (ArchiveStudentAccountsJob $job) use ($expectedIds): bool {
            return queuedStudentIds($job) === $expectedIds;
        });
    });

    test('E1MSJ-NFR-R2: queued path does not mutate statuses synchronously', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');
        $students = User::factory()->count(3)->create(['status' => AccountStatus::VERIFIED->value]);
        $students->each(fn ($u) => $u->assignRole('student'));

        $this->actingAs($admin);
        Queue::fake();

        app(DispatchArchiveStudentAccountsAction::class)
            ->execute(studentQuery(), queueThreshold: 2);

        foreach ($students as $student) {
            expect(User::find($student->id)->status)->toBe(AccountStatus::VERIFIED);
        }
    });

    test('E1MSJ-UC-3: StudentManager archiveAllFiltered routes through the dispatcher', function () {
        $admin = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $admin->assignRole('admin');
        $target = User::factory()->create(['status' => AccountStatus::VERIFIED->value]);
        $target->assignRole('student');

        $this->actingAs($admin);
        Queue::fake();

        Livewire::test(StudentManager::class)
            ->call('archiveAllFiltered');

        expect(User::find($target->id)->status)->toBe(AccountStatus::ARCHIVED);

        Queue::assertNothingPushed();
    });
});
