<?php

declare(strict_types=1);

namespace Modules\Teacher\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;

class Dashboard extends Component
{
    /**
     * Get the student registrations supervised by the current teacher.
     */
    public function getStudentsProperty(): Collection
    {
        /** @var InternshipRegistrationService $service */
        $service = app(InternshipRegistrationService::class);

        return $service->get(['teacher_id' => auth()->id()]);
    }

    /**
     * Render the teacher dashboard view.
     */
    public function render(): View
    {
        return view('teacher::livewire.dashboard')->layout('ui::components.layouts.dashboard', [
            'title' => __('Dasbor Guru'),
        ]);
    }
}
