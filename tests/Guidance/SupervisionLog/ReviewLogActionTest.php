<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Guidance\SupervisionLog\Actions\ReviewLogAction;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('ReviewLogAction', function () {
    test('reviews a submitted supervision log', function () {
        $supervisor = User::factory()->create();
        $log = SupervisionLog::factory()->create([
            'status' => SupervisionLogStatus::SUBMITTED,
        ]);

        $result = app(ReviewLogAction::class)->execute($log, $supervisor, 'Great work, keep it up!');

        expect($result->status)->toBe(SupervisionLogStatus::REVIEWED);
        expect($result->supervisor_feedback)->toBe('Great work, keep it up!');
        expect($result->reviewed_by)->toBe($supervisor->id);
        expect($result->reviewed_at)->not->toBeNull();
    });

    test('throws when log is not in submitted status', function () {
        $supervisor = User::factory()->create();
        $log = SupervisionLog::factory()->create([
            'status' => SupervisionLogStatus::DRAFT,
        ]);

        app(ReviewLogAction::class)->execute($log, $supervisor, 'Feedback');
    })->throws(RejectedException::class);
});
