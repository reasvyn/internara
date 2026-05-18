<?php

declare(strict_types=1);

namespace App\Actions\Presentation;

use App\Actions\Core\LogAuditAction;
use App\Models\Presentation;
use App\Models\PresentationExaminer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SchedulePresentationAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): Presentation
    {
        $validated = Validator::validate($data, [
            'registration_id' => 'required|exists:internship_registrations,id',
            'scheduled_at' => 'required|date',
            'location' => 'nullable|string|max:255',
            'examiner_ids' => 'required|array|min:1|max:5',
            'examiner_ids.*' => 'exists:users,id',
            'notes' => 'nullable|string|max:5000',
        ]);

        return DB::transaction(function () use ($validated) {
            $presentation = Presentation::create([
                'registration_id' => $validated['registration_id'],
                'scheduled_at' => $validated['scheduled_at'],
                'location' => $validated['location'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['examiner_ids'] as $examinerId) {
                PresentationExaminer::create([
                    'presentation_id' => $presentation->id,
                    'examiner_id' => $examinerId,
                ]);
            }

            $this->logAudit->execute(
                action: 'presentation_scheduled',
                subjectType: Presentation::class,
                subjectId: $presentation->id,
                payload: ['registration_id' => $validated['registration_id']],
                module: 'Presentation',
            );

            return $presentation;
        });
    }
}
