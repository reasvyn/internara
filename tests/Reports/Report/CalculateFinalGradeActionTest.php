<?php

declare(strict_types=1);

use App\Reports\Report\Actions\CalculateFinalGradeAction;
use App\Reports\Report\Models\Report;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('CalculateFinalGradeAction', function () {
    test('calculates final grade from existing scores', function () {
        $report = Report::factory()->create([
            'supervisor_score' => 95,
            'teacher_score' => 85,
            'exam_score' => 75,
            'final_score' => null,
            'grade_letter' => null,
        ]);

        $result = app(CalculateFinalGradeAction::class)->execute($report);

        // (95*40 + 85*20 + 75*20 + 0*20) / 100 = 70
        expect($result->final_score)->toBe(70.0);
        expect($result->grade_letter)->toBe('C');
        expect($result->supervisor_score)->toBe(95.0);
        expect($result->teacher_score)->toBe(85.0);
        expect($result->exam_score)->toBe(75.0);
    });

    test('calculates grade B for 80-89', function () {
        $report = Report::factory()->create([
            'supervisor_score' => 100,
            'teacher_score' => 100,
            'exam_score' => 100,
            'final_score' => null,
            'grade_letter' => null,
        ]);

        $result = app(CalculateFinalGradeAction::class)->execute($report);

        // (100*40 + 100*20 + 100*20 + 0*20) / 100 = 80
        expect($result->final_score)->toBe(80.0);
        expect($result->grade_letter)->toBe('B');
    });

    test('calculates grade C for 70-79', function () {
        $report = Report::factory()->create([
            'supervisor_score' => 85,
            'teacher_score' => 80,
            'exam_score' => 80,
            'final_score' => null,
            'grade_letter' => null,
        ]);

        $result = app(CalculateFinalGradeAction::class)->execute($report);

        // (85*40 + 80*20 + 80*20 + 0*20) / 100 = 66
        expect($result->final_score)->toBe(66.0);
        expect($result->grade_letter)->toBe('D');
    });

    test('calculates grade D', function () {
        $report = Report::factory()->create([
            'supervisor_score' => 65,
            'teacher_score' => 65,
            'exam_score' => 65,
            'final_score' => null,
            'grade_letter' => null,
        ]);

        $result = app(CalculateFinalGradeAction::class)->execute($report);

        // (65*40 + 65*20 + 65*20 + 0*20) / 100 = 52
        expect($result->final_score)->toBe(52.0);
        expect($result->grade_letter)->toBe('E');
    });

    test('calculates grade E for below 60', function () {
        $report = Report::factory()->create([
            'supervisor_score' => 50,
            'teacher_score' => 50,
            'exam_score' => 50,
            'final_score' => null,
            'grade_letter' => null,
        ]);

        $result = app(CalculateFinalGradeAction::class)->execute($report);

        // (50*40 + 50*20 + 50*20 + 0*20) / 100 = 40
        expect($result->final_score)->toBe(40.0);
        expect($result->grade_letter)->toBe('E');
    });

    test('clamps final score between 0 and 100', function () {
        $report = Report::factory()->create([
            'supervisor_score' => 150,
            'teacher_score' => 150,
            'exam_score' => 150,
            'final_score' => null,
            'grade_letter' => null,
        ]);

        $result = app(CalculateFinalGradeAction::class)->execute($report);

        expect($result->final_score)->toBe(100.0);
    });
});
