<?php

declare(strict_types=1);

namespace Modules\Assignment\Services;

use Illuminate\Http\UploadedFile;
use Modules\Assignment\Models\Submission;
use Modules\Assignment\Services\Contracts\SubmissionService as Contract;
use Modules\Shared\Services\EloquentQuery;

class SubmissionService extends EloquentQuery implements Contract
{
    public function __construct(Submission $model)
    {
        $this->setModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function submit(string $registrationId, string $assignmentId, mixed $content): Submission
    {
        // Gating Invariant: Briefing/Guidance must be completed if enabled
        $settingService = app(\Modules\Setting\Services\Contracts\SettingService::class);
        $guidanceService = app(\Modules\Guidance\Services\Contracts\HandbookService::class);

        if (
            $settingService->getValue('feature_guidance_enabled', true) &&
            !$guidanceService->hasCompletedMandatory((string) auth()->id())
        ) {
            throw new \Modules\Exception\AppException(
                userMessage: 'guidance::messages.must_complete_guidance',
                code: 403,
            );
        }

        /** @var Submission $submission */
        $submission = $this->model->newQuery()->updateOrCreate(
            ['registration_id' => $registrationId, 'assignment_id' => $assignmentId],
            [
                'student_id' => auth()->id(), // Assuming student is submitting
                'submitted_at' => now(),
            ],
        );

        if ($content instanceof UploadedFile) {
            $submission->setMedia($content, 'file');
        } else {
            $submission->update(['content' => (string) $content]);
        }

        $submission->setStatus('submitted', 'Work submitted by student.');

        return $submission;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $submissionId, ?string $reason = null): Submission
    {
        $submission = $this->find($submissionId);
        $submission->setStatus('verified', $reason ?? 'Submission verified by authorized staff.');

        return $submission;
    }
}
