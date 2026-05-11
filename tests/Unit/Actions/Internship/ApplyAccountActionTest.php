<?php

declare(strict_types=1);

use App\Actions\Internship\ApplyAccountAction;
use App\Models\AccountApplication;
use Database\Factories\InternshipFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates an account application', function () {
        $internship = InternshipFactory::new()->create();

        $application = app(ApplyAccountAction::class)->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'internship_id' => $internship->id,
        ]);

        expect($application)->toBeInstanceOf(AccountApplication::class)
            ->and($application->name)->toBe('John Doe')
            ->and($application->email)->toBe('john@example.com');
    });

    it('throws RuntimeException for duplicate pending application', function () {
        $internship = InternshipFactory::new()->create();

        app(ApplyAccountAction::class)->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'internship_id' => $internship->id,
        ]);

        expect(fn () => app(ApplyAccountAction::class)->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'internship_id' => $internship->id,
        ]))->toThrow(RuntimeException::class, 'An application with this email already exists.');
    });
});
