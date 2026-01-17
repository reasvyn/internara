<?php

declare(strict_types=1);

namespace Modules\Journal\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Journal\Livewire\Forms\JournalForm;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;

class JournalEntryManager extends Component
{
    public JournalForm $form;

    protected JournalService $journalService;

    public function boot(JournalService $journalService): void
    {
        $this->journalService = $journalService;
    }

    public function mount(?string $id = null): void
    {
        if ($id) {
            $entry = $this->journalService->find($id);
            $this->authorize('update', $entry);
            $this->form->setEntry($entry);
        } else {
            $this->authorize('create', \Modules\Journal\Models\JournalEntry::class);
            $this->form->date = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->form->validate();

        try {
            if ($this->form->id) {
                $this->journalService->update($this->form->id, $this->form->except('entry'));
            } else {
                // Find active registration for current student
                $registration = app(InternshipRegistrationService::class)->first([
                    'student_id' => auth()->id(),
                    'latest_status' => 'active' // Assuming active is the status for current placement
                ]);

                if (!$registration) {
                    throw new \Exception(__('internship::messages.no_active_registration'));
                }

                $data = $this->form->except('entry');
                $data['student_id'] = auth()->id();
                $data['registration_id'] = $registration->id;

                $this->journalService->create($data);
            }

            $this->dispatch('notify', message: __('shared::messages.record_saved'), type: 'success');
            $this->redirect(route('journal.index'), navigate: true);
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function render(): View
    {
        return view('journal::livewire.journal-entry-manager')->layout('dashboard::components.layouts.dashboard', [
            'title' => $this->form->id ? __('Edit Jurnal') : __('Buat Jurnal Baru'),
        ]);
    }
}