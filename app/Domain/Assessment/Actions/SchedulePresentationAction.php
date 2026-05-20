<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Presentation;
use App\Domain\Assessment\Models\PresentationExaminer;
use App\Domain\Core\Actions\BaseAction;
use Illuminate\Support\Facades\Validator;

class SchedulePresentationAction extends BaseAction
{
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

        return $this->transaction(function () use ($validated) {
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

            $this->log('presentation_scheduled', $presentation, ['registration_id' => $validated['registration_id']]);

            return $presentation;
        });
    }
}
