<?php

declare(strict_types=1);

use App\Journals\SupervisionLog\Actions\VerifySupervisionLogAction;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('verifies supervision log', function () {
    $verifier = User::factory()->create();
    $log = SupervisionLog::factory()->create();

    $result = app(VerifySupervisionLogAction::class)->execute($log, $verifier);

    expect($result->is_verified)->toBeTrue();
    expect($result->verified_by)->toBe($verifier->id);
});

test('throws when log is already verified', function () {
    $verifier = User::factory()->create();
    $log = SupervisionLog::factory()->create(['is_verified' => true, 'status' => 'verified']);

    app(VerifySupervisionLogAction::class)->execute($log, $verifier);
})->throws(RuntimeException::class, 'This supervision log has already been verified.');
