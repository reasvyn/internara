<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\PresentationExaminer;
use App\Domain\Core\Actions\BaseAction;
use Illuminate\Support\Facades\Validator;

final class ScorePresentationAction extends BaseAction
{
    public function execute(PresentationExaminer $examiner, array $data): PresentationExaminer
    {
        $validated = Validator::validate($data, [
            'score' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);

        return $this->transaction(function () use ($examiner, $validated) {
            $examiner->update($validated);

            $this->log('presentation_scored', $examiner, ['presentation_id' => $examiner->presentation_id]);

            return $examiner->fresh();
        });
    }
}
