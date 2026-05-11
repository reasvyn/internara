<?php

declare(strict_types=1);

use App\Actions\Mentor\VerifySupervisionLogAction;
use App\Enums\SupervisionLogStatus;
use App\Models\SupervisionLog;
use Database\Factories\SupervisionLogFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeAll(function () {
    require_once getcwd().'/app/Models/SupervisionLog.php';
    class_alias(
        SupervisionLog::class,
        App\Models\Mentor\SupervisionLog::class,
    );
    require_once getcwd().'/app/Enums/Mentor/SupervisionLogStatus.php';
    class_alias(
        App\Enums\Mentor\SupervisionLogStatus::class,
        SupervisionLogStatus::class,
    );
});

describe('execute', function () {
    it('verifies a supervision log', function () {
        $log = SupervisionLogFactory::new()->create(['is_verified' => false]);
        $verifier = UserFactory::new()->create();

        $result = app(VerifySupervisionLogAction::class)->execute($log, $verifier);

        expect($result->is_verified)->toBeTrue()
            ->and($result->verified_by)->toBe($verifier->id)
            ->and($result->verified_at)->not->toBeNull();
    });

    it('throws RuntimeException if already verified', function () {
        $log = SupervisionLogFactory::new()->create(['is_verified' => true]);
        $verifier = UserFactory::new()->create();

        expect(fn () => app(VerifySupervisionLogAction::class)->execute($log, $verifier))
            ->toThrow(RuntimeException::class, 'already been verified');
    });
});
