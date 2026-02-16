<?php

declare(strict_types=1);

namespace Modules\Student\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\RegistrationService;

class Dashboard extends Component
{
    /**
     * Get the current student registration.
     */
    #[Computed]
    public function registration(): ?object
    {
        return app(RegistrationService::class)->first([
            'student_id' => Auth::id(),
        ]);
    }

    /**
     * Get the score card for the current registration.
     */
    #[Computed]
    public function scoreCard(): array
    {
        if (!$this->registration) {
            return ['final_grade' => null];
        }

        return app(AssessmentService::class)->getScoreCard($this->registration->id);
    }

    /**
     * Render the student dashboard view.
     */
    public function render(): View
    {
        return view('student::livewire.dashboard')->layout('ui::components.layouts.dashboard', [
            'title' => __('student::ui.dashboard.title'),
        ]);
    }
}
