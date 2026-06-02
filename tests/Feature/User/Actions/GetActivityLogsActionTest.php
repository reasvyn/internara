<?php

declare(strict_types=1);

use App\Domain\Core\Models\ActivityLog;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('ActivityLog query', function () {
    it('returns paginated activity logs for a user', function () {
        $user = User::factory()->create();
        $userType = get_class($user);

        for ($i = 0; $i < 5; $i++) {
            ActivityLog::create([
                'log_name' => 'default',
                'description' => 'test',
                'causer_id' => $user->id,
                'causer_type' => $userType,
            ]);
        }

        $result = ActivityLog::where('causer_id', $user->id)
            ->where('causer_type', $userType)
            ->latest()
            ->paginate(50);

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($result->total())->toBe(5);
    });

    it('returns empty paginator when no activity', function () {
        $user = User::factory()->create();

        $result = ActivityLog::where('causer_id', $user->id)
            ->latest()
            ->paginate(50);

        expect($result->total())->toBe(0);
    });

    it('respects per page parameter', function () {
        $result = ActivityLog::latest()->paginate(10);

        expect($result->perPage())->toBe(10);
    });
});
