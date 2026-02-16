<?php

declare(strict_types=1);

namespace Modules\Assessment\Livewire;

use Livewire\Component;
use Modules\Assessment\Services\Contracts\CompetencyService;

class SkillProgress extends Component
{
    public string $registrationId;

    public array $stats = [];

    public function mount(CompetencyService $service, ?string $registrationId = null)
    {
        if (!$registrationId) {
            $registration = app(
                \Modules\Internship\Services\Contracts\RegistrationService::class,
            )->first([
                'student_id' => auth()->id(),
                'latest_status' => 'active',
            ]);
            $registrationId = $registration?->id;
        }

        if ($registrationId) {
            $this->registrationId = $registrationId;
            $this->stats = $service->getProgressStats($registrationId);
        }
    }

    public function render()
    {
        return view('assessment::livewire.skill-progress');
    }
}
