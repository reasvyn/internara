<?php

declare(strict_types=1);

use App\Actions\Evaluation\EvaluateMentorAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('returns pending_implementation status', function () {
        $evaluator = UserFactory::new()->create();
        $mentor = UserFactory::new()->create();

        $result = app(EvaluateMentorAction::class)->execute($evaluator, $mentor, ['rating' => 4]);

        expect($result['status'])->toBe('pending_implementation')
            ->and($result['evaluator_id'])->toBe($evaluator->id)
            ->and($result['mentor_id'])->toBe($mentor->id);
    });
});
