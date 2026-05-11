<?php

declare(strict_types=1);

use App\Actions\Internship\VerifyRegistrationAction;
use App\Models\Registration;
use Database\Factories\InternshipFactory;
use Database\Factories\MenteeFactory;
use Database\Factories\PlacementFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('verifies a pending registration', function () {
        $mentee = MenteeFactory::new()->create();
        $internship = InternshipFactory::new()->create();
        $placement = PlacementFactory::new()->create([
            'internship_id' => $internship->id,
            'quota' => 10,
        ]);
        $registration = Registration::create([
            'mentee_id' => $mentee->id,
            'internship_id' => $internship->id,
        ]);
        $registration->setStatus('pending', 'Initial registration');

        $result = app(VerifyRegistrationAction::class)->execute($registration->id, [
            'placement_id' => $placement->id,
        ]);

        expect($result->placement_id)->toBe($placement->id);
    });

    it('throws RuntimeException if registration is not pending', function () {
        $mentee = MenteeFactory::new()->create();
        $internship = InternshipFactory::new()->create();
        $registration = Registration::create([
            'mentee_id' => $mentee->id,
            'internship_id' => $internship->id,
        ]);
        $registration->setStatus('active', 'Active');
        $placement = PlacementFactory::new()->create([
            'internship_id' => $internship->id,
            'quota' => 10,
        ]);

        expect(fn () => app(VerifyRegistrationAction::class)->execute($registration->id, [
            'placement_id' => $placement->id,
        ]))->toThrow(RuntimeException::class, 'Registration is not in pending status.');
    });
});
