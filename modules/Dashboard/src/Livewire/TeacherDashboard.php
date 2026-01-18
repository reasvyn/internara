<?php

declare(strict_types=1);

namespace Modules\Dashboard\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;

class TeacherDashboard extends Component
{
    /**
     * Get the student registrations supervised by the current teacher.
     */
    public function getStudentsProperty(): \Illuminate\Support\Collection
    {
        /** @var InternshipRegistrationService $service */
        $service = app(InternshipRegistrationService::class);

        return $service->get(['teacher_id' => auth()->id()]);
    }

    public function render(): View
    {
        return view('dashboard::livewire.teacher-dashboard')->layout(
            'dashboard::components.layouts.dashboard',
            [
                'title' => 'Dasbor Guru',
            ],
        );
    }
}
