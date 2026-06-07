<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Livewire;

use App\Journals\AbsenceRequest\Actions\SubmitAbsenceAction;
use App\Journals\AbsenceRequest\Enums\AbsenceReasonType;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AbsenceRequestForm extends Component
{
    public string $startDate = '';

    public string $endDate = '';

    public string $reasonType = '';

    public string $reasonDescription = '';

    protected function rules(): array
    {
        return [
            'startDate' => 'required|date|after_or_equal:today',
            'endDate' => 'required|date|after_or_equal:startDate',
            'reasonType' => 'required|string|in:sick,permission,emergency,other',
            'reasonDescription' => 'required|string|min:10|max:1000',
        ];
    }

    public function submit(SubmitAbsenceAction $action): void
    {
        $this->validate();

        $registration = auth()
            ->user()
            ->registrations()
            ->get()
            ->first(fn ($reg) => $reg->hasStatus('active'));

        if (! $registration) {
            flash()->error('No active internship registration found.');

            return;
        }

        $action->execute(auth()->user(), [
            'registration_id' => $registration->id,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'reason_type' => $this->reasonType,
            'reason_description' => $this->reasonDescription,
        ]);

        $this->reset(['startDate', 'endDate', 'reasonType', 'reasonDescription']);
        flash()->success('Absence request submitted successfully.');
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('journals.absence-request.absence-request-form', [
            'reasonTypes' => AbsenceReasonType::cases(),
            'existingRequests' => AbsenceRequest::where('user_id', auth()->id())
                ->latest()
                ->paginate(10),
        ]);
    }
}
