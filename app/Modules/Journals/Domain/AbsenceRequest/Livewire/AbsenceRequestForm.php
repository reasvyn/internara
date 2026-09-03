<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\AbsenceRequest\Livewire;

use App\Modules\Core\Livewire\BaseFormView;
use App\Modules\Journals\Domain\AbsenceRequest\Actions\SubmitAbsenceAction;
use App\Modules\Journals\Domain\AbsenceRequest\Data\SubmitAbsenceData;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceReasonType;
use App\Modules\Journals\Domain\AbsenceRequest\Models\AbsenceRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use TallStackUi\Traits\Interactions;

class AbsenceRequestForm extends BaseFormView
{
    use Interactions;

    #[Url(as: 'date', except: '')]
    public string $startDate = '';

    public string $reasonType = '';

    public string $reasonDescription = '';

    protected function rules(): array
    {
        // A missed day can only be explained after the fact, so past dates must be
        // accepted — bounded by the internship period so the date stays meaningful.
        $registration = auth()->user()?->getActiveRegistration();

        $bounds = array_filter([
            $registration?->start_date ? 'after_or_equal:'.$registration->start_date->toDateString() : null,
            $registration?->end_date ? 'before_or_equal:'.$registration->end_date->toDateString() : null,
        ]);

        return [
            'startDate' => implode('|', ['required', 'date', ...$bounds]),
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
            'existingRequests' => AbsenceRequest::where('user_id', auth()->id())
                ->latest('date')
                ->paginate(10),
        ]);
    }
}
