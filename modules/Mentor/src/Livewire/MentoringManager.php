<?php

declare(strict_types=1);

namespace Modules\Mentor\Livewire;

use Livewire\Attributes\Validate;
use Livewire\Component;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Mentor\Services\Contracts\MentoringService;

class MentoringManager extends Component
{
    public string $registrationId;

    public bool $visitModal = false;

    public bool $logModal = false;

    #[Validate('required|date')]
    public string $visit_date = '';

    #[Validate('nullable|string')]
    public string $visit_notes = '';

    #[Validate('required|string|max:255')]
    public string $log_subject = '';

    #[Validate('required|string')]
    public string $log_content = '';

    #[Validate('required|string')]
    public string $log_type = 'feedback';

    public function mount(string $registrationId)
    {
        $this->registrationId = $registrationId;
        $this->visit_date = now()->format('Y-m-d');
    }

    public function recordVisit(MentoringService $service)
    {
        $this->validate([
            'visit_date' => 'required|date',
            'visit_notes' => 'nullable|string',
        ]);

        $service->recordVisit([
            'registration_id' => $this->registrationId,
            'teacher_id' => auth()->id(),
            'visit_date' => $this->visit_date,
            'notes' => $this->visit_notes,
        ]);

        $this->reset(['visit_notes']);
        $this->visitModal = false;
        $this->dispatch('notify', message: __('Kunjungan berhasil dicatat'), type: 'success');
    }

    public function recordLog(MentoringService $service)
    {
        $this->validate([
            'log_subject' => 'required|string|max:255',
            'log_content' => 'required|string',
            'log_type' => 'required|string',
        ]);

        $service->recordLog([
            'registration_id' => $this->registrationId,
            'causer_id' => auth()->id(),
            'type' => $this->log_type,
            'subject' => $this->log_subject,
            'content' => $this->log_content,
        ]);

        $this->reset(['log_subject', 'log_content', 'log_type']);
        $this->logModal = false;
        $this->dispatch('notify', message: __('Log bimbingan berhasil dicatat'), type: 'success');
    }

    public function render()
    {
        $registration = InternshipRegistration::with([
            'student.profile',
            'internship',
            'placement',
        ])->findOrFail($this->registrationId);

        $stats = app(MentoringService::class)->getMentoringStats($this->registrationId);

        return view('mentor::livewire.mentoring-manager', [
            'registration' => $registration,
            'stats' => $stats,
            'visits' => \Modules\Mentor\Models\MentoringVisit::where(
                'registration_id',
                $this->registrationId,
            )
                ->latest('visit_date')
                ->get(),
            'logs' => \Modules\Mentor\Models\MentoringLog::where(
                'registration_id',
                $this->registrationId,
            )
                ->latest()
                ->get(),
        ])->layout('ui::components.layouts.dashboard', ['title' => __('Manajemen Pembimbingan')]);
    }
}
