<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('R6BMW-FR-RPT-012: StudentReportFactory', function (): void {
    test('finalized() sets FINALIZED status with actor and timestamp', function (): void {
        $report = StudentReport::factory()->finalized()->create();

        expect($report->status)->toBe(StudentReportStatus::FINALIZED)
            ->and($report->finalized_by)->not->toBeNull()
            ->and($report->finalized_at)->not->toBeNull();
    });
});
