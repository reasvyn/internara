<?php

declare(strict_types=1);

namespace Modules\Teacher\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Internship\Services\Contracts\RegistrationService;

class Dashboard extends Component
{
    /**
     * Get the student registrations supervised by the current teacher.
     */
    public function getStudentsProperty(): Collection
    {
        /** @var RegistrationService $service */
        $service = app(RegistrationService::class);

        return $service->get(['teacher_id' => auth()->id()]);
    }

    /**
     * Get readiness status for a specific registration.
     */
    public function getReadiness(string $id): array
    {
        return app(
            \Modules\Assessment\Services\Contracts\AssessmentService::class,
        )->getReadinessStatus($id);
    }

    /**
     * Render the teacher dashboard view.
     */
    public function render(): View
    {
        return view('teacher::livewire.dashboard')->layout('ui::components.layouts.dashboard', [
            'title' => __('teacher::ui.dashboard.title').
                ' | '.
                setting('brand_name', setting('app_name')),
        ]);
    }
}
