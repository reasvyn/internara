<?php

declare(strict_types=1);

namespace Modules\Mentor\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Internship\Services\Contracts\RegistrationService;

class Dashboard extends Component
{
    /**
     * Get the student registrations supervised by the current mentor.
     */
    public function getStudentsProperty(): Collection
    {
        /** @var RegistrationService $service */
        $service = app(RegistrationService::class);

        return $service->get(['mentor_id' => auth()->id()]);
    }

    /**
     * Render the mentor dashboard view.
     */
    public function render(): View
    {
        return view('mentor::livewire.dashboard')->layout('ui::components.layouts.dashboard', [
            'title' => __('mentor::ui.dashboard.title').
                ' | '.
                setting('brand_name', setting('app_name')),
        ]);
    }
}
