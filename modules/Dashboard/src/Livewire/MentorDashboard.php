<?php

declare(strict_types=1);

namespace Modules\Dashboard\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;

class MentorDashboard extends Component
{
    /**
     * Get the student registrations supervised by the current mentor.
     */
    public function getStudentsProperty(): \Illuminate\Support\Collection
    {
        /** @var InternshipRegistrationService $service */
        $service = app(InternshipRegistrationService::class);

        return $service->get(['mentor_id' => auth()->id()]);
    }

    public function render(): View
    {
        return view('dashboard::livewire.mentor-dashboard')->layout(
            'dashboard::components.layouts.dashboard',
            [
                'title' => 'Dasbor Mentor',
            ],
        );
    }
}
