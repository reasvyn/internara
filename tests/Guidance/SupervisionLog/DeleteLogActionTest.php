<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Guidance\SupervisionLog\Actions\DeleteLogAction;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('DeleteLogAction', function () {
    test('deletes a draft supervision log', function () {
        $log = SupervisionLog::factory()->create([
            'status' => SupervisionLogStatus::DRAFT,
        ]);

        app(DeleteLogAction::class)->execute($log);

        expect(SupervisionLog::find($log->id))->toBeNull();
    });

    test('throws when supervision log is not a draft', function () {
        $log = SupervisionLog::factory()->create([
            'status' => SupervisionLogStatus::SUBMITTED,
        ]);

        app(DeleteLogAction::class)->execute($log);
    })->throws(RejectedException::class);
});
