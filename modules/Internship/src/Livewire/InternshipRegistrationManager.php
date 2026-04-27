<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Internship\Livewire\Forms\RegistrationForm;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Services\Contracts\RegistrationService;

/**
 * Class InternshipRegistrationManager (Student)
 *
 * Handles student self-registration for internship programs.
 * Allows students to choose existing placements or propose new ones.
 */
class InternshipRegistrationManager extends Component
{
    public RegistrationForm $form;

    public bool $proposeNewPartner = false;

    public string $proposedCompanyName = '';

    public string $proposedCompanyAddress = '';

    public function mount(): void
    {
        $this->form->student_id = auth()->id();
        $this->form->status = 'pending';
    }

    /**
     * Get available internship programs for students.
     */
    #[Computed]
    public function programs()
    {
        return Internship::query()
            ->orderBy('date_start', 'desc')
            ->get()
            ->map(
                fn(Internship $internship) => [
                    'id' => $internship->id,
                    'name' => "{$internship->title} ({$internship->academic_year})",
                ],
            )
            ->toArray();
    }

    /**
     * Get available placements for the selected program.
     */
    #[Computed]
    public function availablePlacements()
    {
        if (!$this->form->internship_id) {
            return [];
        }

        return InternshipPlacement::query()
            ->where('internship_id', $this->form->internship_id)
            ->with('company')
            ->get()
            ->map(
                fn(InternshipPlacement $placement) => [
                    'id' => $placement->id,
                    'name' => $placement->company?->name ?? 'Unknown',
                ],
            )
            ->toArray();
    }

    /**
     * Submit registration.
     */
    public function submit(): void
    {
        $this->form->validate();

        try {
            $data = $this->form->all();

            if ($this->proposeNewPartner) {
                $data['proposed_company_name'] = $this->proposedCompanyName;
                $data['proposed_company_address'] = $this->proposedCompanyAddress;
                // Note: placement_id should be null when proposing
                $data['placement_id'] = null;
            }

            app(RegistrationService::class)->register($data);

            flash()->success(__('internship::ui.registration_success'));
            $this->redirect(route('dashboard'));
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render(): View
    {
        return view('internship::livewire.internship-registration-manager')->layout(
            'ui::components.layouts.dashboard',
            [
                'title' => __('internship::ui.registration_title') . ' | ' . setting('brand_name'),
            ],
        );
    }
}
