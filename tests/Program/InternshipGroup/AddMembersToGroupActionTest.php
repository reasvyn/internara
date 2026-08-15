<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Program\InternshipGroup\Actions\AddMembersToGroupAction;
use App\Program\InternshipGroup\Models\InternshipGroup;
use App\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('IT0OE-FR-MM9: AddMembersToGroupAction creates all rows with joined_at = now()', function () {
    $group = InternshipGroup::factory()->create();
    $registrations = Registration::factory()->count(2)->create();

    $rows = $registrations->map(fn (Registration $registration) => [
        'role' => 'student',
        'registration_id' => $registration->id,
        'mentor_id' => null,
    ])->all();

    $count = app(AddMembersToGroupAction::class)->execute($group, $rows);

    expect($count)->toBe(2);
    expect($group->members()->count())->toBe(2);
    expect($group->members()->whereNull('joined_at')->count())->toBe(0);
});

it('IT0OE-FR-MM9: AddMembersToGroupAction rolls back the whole batch on DB constraint violation', function () {
    $group = InternshipGroup::factory()->create();
    $registration = Registration::factory()->create();

    $rows = [
        ['role' => 'student', 'registration_id' => $registration->id, 'mentor_id' => null],
        ['role' => 'student', 'registration_id' => $registration->id, 'mentor_id' => null],
    ];

    expect(fn () => app(AddMembersToGroupAction::class)->execute($group, $rows))
        ->toThrow(QueryException::class);

    expect($group->members()->count())->toBe(0);
});

it('IT0OE-FR-MM9: AddMembersToGroupAction supports mentor roles in the batch', function () {
    $group = InternshipGroup::factory()->create();
    $mentors = User::factory()->count(2)->create();

    $rows = $mentors->map(fn ($mentor) => [
        'role' => 'school_teacher',
        'registration_id' => null,
        'mentor_id' => $mentor->id,
    ])->all();

    $count = app(AddMembersToGroupAction::class)->execute($group, $rows);

    expect($count)->toBe(2);
    expect($group->members()->where('role', 'school_teacher')->count())->toBe(2);
});
