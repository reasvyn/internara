<?php

declare(strict_types=1);

use App\Domain\User\Actions\GetStudentDashboardDataAction;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('GetStudentDashboardDataAction', function () {
    it('returns empty data for user without active registration', function () {
        $user = User::factory()->create();

        $data = app(GetStudentDashboardDataAction::class)->execute($user->id);

        expect($data['registration'])->toBeNull();
        expect($data['totalJournals'])->toBe(0);
        expect($data['verifiedJournals'])->toBe(0);
    });

    it('throws for non-existent user', function () {
        expect(fn () => app(GetStudentDashboardDataAction::class)->execute('non-existent-id'))
            ->toThrow(RuntimeException::class);
    });
});
