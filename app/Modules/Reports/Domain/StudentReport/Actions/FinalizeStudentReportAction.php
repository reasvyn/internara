<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Reports\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Reports\Domain\StudentReport\Events\StudentReportFinalized;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;

final class FinalizeStudentReportAction extends BaseCommandAction
{
    public function execute(StudentReport $studentReport, string $finalizedBy): StudentReport
    {
        if ($studentReport->status->isTerminal()) {
            throw new RejectedException(__('report.already_finalized'));
        }

        return $this->transaction(function () use ($studentReport, $finalizedBy) {
            $studentReport->update([
                'status' => StudentReportStatus::FINALIZED->value,
                'finalized_by' => $finalizedBy,
                'finalized_at' => now(),
            ]);

            $this->log('report_finalized', $studentReport, [
                'final_score' => $studentReport->final_score,
                'grade_letter' => $studentReport->grade_letter,
            ]);

            $this->dispatchEvent(new StudentReportFinalized($studentReport));

            return $studentReport->fresh();
        });
    }
}
