<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Livewire;

use App\Journals\AbsenceRequest\Actions\SubmitAbsenceAction;
use App\Journals\AbsenceRequest\Enums\AbsenceReasonType;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\Journals\Attendance\Models\Attendance;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AbsenceRequestForm extends Component
{
    public string $startDate = '';

    public string $reasonType = '';

    public string $reasonDescription = '';

    protected function rules(): array
    {
        return [
            'startDate' => 'required|date|after_or_equal:today',
            'reasonType' => 'required|string|in:sick,permission,emergency,other',
            'reasonDescription' => 'required|string|min:10|max:1000',
        ];
    }

    public function submit(SubmitAbsenceAction $action): void
    {
        $this->authorize('create', AbsenceRequest::class);

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

        $action->execute(auth()->user(), $registration->id, [
            'start_date' => $this->startDate,
            'reason_type' => $this->reasonType,
            'reason_description' => $this->reasonDescription,
        ]);

        $this->reset(['startDate', 'reasonType', 'reasonDescription']);
        flash()->success('Absence request submitted successfully.');
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('journals.absence-request.absence-request-form', [
            'reasonTypes' => AbsenceReasonType::cases(),
            'existingRequests' => Attendance::where('user_id', auth()->id())
                ->whereNotNull('absence_type')
                ->latest()
                ->paginate(10),
        ]);
    }
}
