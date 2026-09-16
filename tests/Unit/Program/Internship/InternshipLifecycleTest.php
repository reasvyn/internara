<?php

declare(strict_types=1);

use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\Program\Domain\Internship\Models\Internship;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('7C5WM: internship lifecycle unit gaps', function (): void {
    test('7C5WM-FR-LIFE-015: completed and cancelled hold the line except completed to archived', function (): void {
        expect(InternshipStatus::COMPLETED->validTransitions())->toBe([InternshipStatus::ARCHIVED]);
        expect(InternshipStatus::CANCELLED->validTransitions())->toBe([]);

        expect(InternshipStatus::COMPLETED->canTransitionTo(InternshipStatus::ARCHIVED))->toBeTrue();
        expect(InternshipStatus::COMPLETED->canTransitionTo(InternshipStatus::DRAFT))->toBeFalse();
        expect(InternshipStatus::COMPLETED->canTransitionTo(InternshipStatus::PUBLISHED))->toBeFalse();
        expect(InternshipStatus::COMPLETED->canTransitionTo(InternshipStatus::ACTIVE))->toBeFalse();
        expect(InternshipStatus::COMPLETED->canTransitionTo(InternshipStatus::CANCELLED))->toBeFalse();
        expect(InternshipStatus::CANCELLED->canTransitionTo(InternshipStatus::ARCHIVED))->toBeFalse();
        expect(InternshipStatus::CANCELLED->canTransitionTo(InternshipStatus::DRAFT))->toBeFalse();
        expect(InternshipStatus::CANCELLED->canTransitionTo(InternshipStatus::COMPLETED))->toBeFalse();
        expect(InternshipStatus::ARCHIVED->canTransitionTo(InternshipStatus::ARCHIVED))->toBeFalse();
    });

    test('7C5WM-FR-LIFE-016: only published and active accept registrations across all six cases', function (): void {
        expect(InternshipStatus::PUBLISHED->isAcceptingRegistrations())->toBeTrue();
        expect(InternshipStatus::ACTIVE->isAcceptingRegistrations())->toBeTrue();
        expect(InternshipStatus::DRAFT->isAcceptingRegistrations())->toBeFalse();
        expect(InternshipStatus::COMPLETED->isAcceptingRegistrations())->toBeFalse();
        expect(InternshipStatus::CANCELLED->isAcceptingRegistrations())->toBeFalse();
        expect(InternshipStatus::ARCHIVED->isAcceptingRegistrations())->toBeFalse();
    });

    test('7C5WM-FR-LIFE-001: fillable allow-list carries every spec-named attribute', function (): void {
        $fillable = (new Internship)->getFillable();

        foreach ([
            'academic_year_id',
            'name',
            'start_date',
            'end_date',
            'description',
            'status',
            'phases',
            'required_document_ids',
            'grading_weights',
        ] as $attribute) {
            expect($fillable)->toContain($attribute);
        }
    });

    test('7C5WM-FR-LIFE-002: casts hand out a real status enum, dates, and arrays', function (): void {
        $casts = (new Internship)->getCasts();

        expect($casts['status'])->toBe(InternshipStatus::class);
        expect($casts['start_date'])->toBe('date');
        expect($casts['end_date'])->toBe('date');
        expect($casts['phases'])->toBe('json');
        expect($casts['required_document_ids'])->toBe('json');
        expect($casts['grading_weights'])->toBe('json');

        $internship = new Internship([
            'status' => 'active',
            'start_date' => '2026-01-05',
            'phases' => [['name' => 'Persiapan']],
        ]);

        expect($internship->status)->toBe(InternshipStatus::ACTIVE);
        expect($internship->start_date)->toBeInstanceOf(Carbon\Carbon::class);
        expect($internship->phases)->toBe([['name' => 'Persiapan']]);
    });

    test('7C5WM-FR-LIFE-003: relations wire the year link and both dependent collections', function (): void {
        $internship = new Internship;

        expect($internship->academicYear())->toBeInstanceOf(BelongsTo::class);
        expect($internship->placements())->toBeInstanceOf(HasMany::class);
        expect($internship->registrations())->toBeInstanceOf(HasMany::class);
        expect($internship->academicYear()->getForeignKeyName())->toBe('academic_year_id');
    });

    test('7C5WM-NFR-LIFE-009: lifecycle keys resolve in English and Indonesian', function (): void {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            expect(__('internship.not_found'))->not->toBe('internship.not_found');
            expect(__('internship.not_accepting_registrations'))->not->toBe('internship.not_accepting_registrations');
            expect(__('internship.invalid_status_transition', ['from' => 'x', 'to' => 'y']))
                ->not->toBe('internship.invalid_status_transition');
            expect(InternshipStatus::ARCHIVED->label())->not->toBe('common.enums.archived');
        }

        app()->setLocale('en');
    });
});
