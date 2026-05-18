<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Actions\Core\LogAuditAction;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateReportAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): Report
    {
        $validated = Validator::validate($data, [
            'registration_id' => 'required|exists:internship_registrations,id',
            'title' => 'required|string|max:255',
            'chapter_structure' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($validated) {
            $report = Report::create([
                'registration_id' => $validated['registration_id'],
                'title' => $validated['title'],
                'chapter_structure' => $validated['chapter_structure'] ?? null,
            ]);

            $this->logAudit->execute(
                action: 'report_created',
                subjectType: Report::class,
                subjectId: $report->id,
                payload: ['title' => $report->title],
                module: 'Report',
            );

            return $report;
        });
    }
}
