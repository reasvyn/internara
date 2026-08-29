<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\AbsenceRequest\Livewire;

use App\Modules\Core\Livewire\BaseFormView;
use App\Modules\Journals\Domain\AbsenceRequest\Actions\SubmitAbsenceAction;
use App\Modules\Journals\Domain\AbsenceRequest\Data\SubmitAbsenceData;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceReasonType;
use App\Modules\Journals\Domain\AbsenceRequest\Models\AbsenceRequest;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class AbsenceRequestForm extends BaseFormView
{
    use Interactions;

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
            $this->toast()->error(__('journals.no_active_registration'))->send();

            return;
        }

        $this->handleSave(function () use ($registration, $action) {
            $action->execute(new SubmitAbsenceData(
                userId: auth()->id(),
                registrationId: $registration->id,
                data: [
                    'start_date' => $this->startDate,
                    'reason_type' => $this->reasonType,
                    'reason_description' => $this->reasonDescription,
                ],
            ));

            $this->reset(['startDate', 'reasonType', 'reasonDescription']);
            $this->toast()->success(__('journals.absence.submitted'))->send();
        });
    }

    #[Layout('ui::layouts.app')]
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
