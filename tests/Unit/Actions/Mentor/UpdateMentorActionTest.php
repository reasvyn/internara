<?php

declare(strict_types=1);

use App\Actions\Mentor\UpdateMentorAction;
use Database\Factories\MentorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('updates a mentor record', function () {
        $mentor = MentorFactory::new()->create();

        $result = app(UpdateMentorAction::class)->execute($mentor, ['is_active' => false]);

        expect($result->is_active)->toBeFalse();
    });
});
