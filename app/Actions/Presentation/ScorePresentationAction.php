<?php

declare(strict_types=1);

namespace App\Actions\Presentation;

use App\Actions\Core\LogAuditAction;
use App\Models\PresentationExaminer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScorePresentationAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(PresentationExaminer $examiner, array $data): PresentationExaminer
    {
        $validated = Validator::validate($data, [
            'score' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($examiner, $validated) {
            $examiner->update($validated);

            $this->logAudit->execute(
                action: 'presentation_scored',
                subjectType: PresentationExaminer::class,
                subjectId: $examiner->id,
                payload: ['presentation_id' => $examiner->presentation_id],
                module: 'Presentation',
            );

            return $examiner->fresh();
        });
    }
}
