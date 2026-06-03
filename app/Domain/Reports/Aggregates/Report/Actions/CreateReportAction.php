<?php

declare(strict_types=1);

namespace App\Domain\Reports\Aggregates\Report\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Reports\Aggregates\Report\Models\Report;
use Illuminate\Support\Facades\Validator;

final class CreateReportAction extends BaseAction
{
    public function execute(array $data): Report
    {
        $validated = Validator::validate($data, [
            'registration_id' => 'required|exists:registrations,id',
            'title' => 'required|string|max:255',
            'chapter_structure' => 'nullable|array',
        ]);

        return $this->transaction(function () use ($validated) {
            $report = Report::create([
                'registration_id' => $validated['registration_id'],
                'title' => $validated['title'],
                'chapter_structure' => $validated['chapter_structure'] ?? null,
            ]);

            $this->log('report_created', $report, ['title' => $report->title]);

            return $report;
        });
    }
}
