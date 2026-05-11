<?php

declare(strict_types=1);

use App\Actions\Mentor\VerifySupervisionLogAction;
use Database\Factories\SupervisionLogFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('verifies a supervision log', function () {
        $log = SupervisionLogFactory::new()->create(['is_verified' => false]);
        $verifier = UserFactory::new()->create();

        $result = app(VerifySupervisionLogAction::class)->execute($log, $verifier);

        expect($result->is_verified)->toBeTrue()
            ->and($result->verified_at)->not->toBeNull();
    });

    it('throws RuntimeException if already verified', function () {
        $log = SupervisionLogFactory::new()->create(['is_verified' => true]);
        $verifier = UserFactory::new()->create();

        expect(fn () => app(VerifySupervisionLogAction::class)->execute($log, $verifier))
            ->toThrow(RuntimeException::class, 'already been verified');
    });
});
