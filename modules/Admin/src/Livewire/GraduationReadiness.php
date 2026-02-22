<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\RegistrationService;

class GraduationReadiness extends Component
{
    use WithPagination;

    public string $search = '';

    public function getReadiness(string $id): array
    {
        return app(AssessmentService::class)->getReadinessStatus($id);
    }

    public function render()
    {
        $query = app(RegistrationService::class)->query(['latest_status' => 'active']);

        if ($this->search) {
            $query->whereHas('student', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')->orWhere(
                    'username',
                    'like',
                    '%'.$this->search.'%',
                );
            });
        }

        return view('admin::livewire.graduation-readiness', [
            'registrations' => $query->with(['student', 'placement'])->paginate(15),
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('admin::ui.menu.readiness') . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
