<?php

declare(strict_types=1);

namespace App\Domain\Registration\Livewire;

use App\Domain\Mentor\Models\Mentor;
use App\Domain\Placement\Models\Placement;
use App\Domain\Registration\Actions\VerifyRegistrationAction;
use App\Domain\Registration\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RegistrationVerification extends Component
{
    public ?string $processId = null;

    public string $placement_id = '';

    public array $mentor_ids = [];

    public bool $showProcessModal = false;

    #[Computed]
    public function pendingRegistrations(): Collection
    {
        return Registration::with(['mentee.user', 'internship', 'documents'])
            ->where('placement_id', null)
            ->currentStatus('pending')
            ->latest()
            ->get();
    }

    #[Computed]
    public function selectedRegistration(): ?Registration
    {
        if ($this->processId === null) {
            return null;
        }

        return Registration::with('internship')->find($this->processId);
    }

    #[Computed]
    public function availablePlacements(): Collection
    {
        $registration = $this->selectedRegistration;
        if ($registration === null) {
            return new Collection;
        }

        return Placement::with('company')
            ->where('internship_id', $registration->internship_id)
            ->get()
            ->filter(fn ($p) => ! $p->asPlacementCapacity()->isFull())
            ->values();
    }

    #[Computed]
    public function mentors(): Collection
    {
        return Mentor::with('user')->where('is_active', true)->get();
    }

    public function process(string $id): void
    {
        $this->resetErrorBag();
        $this->reset(['placement_id', 'mentor_ids']);

        $registration = Registration::with('internship')->findOrFail($id);

        abort_if(! $registration->hasStatus('pending'), 422, 'Registration is no longer pending.');

        $this->processId = $id;
        $this->showProcessModal = true;
    }

    public function confirmProcess(VerifyRegistrationAction $action): void
    {
        $this->validate([
            'placement_id' => 'required|exists:placements,id',
            'mentor_ids' => 'nullable|array',
            'mentor_ids.*' => 'exists:mentors,id',
        ]);

        try {
            $action->execute($this->processId, [
                'placement_id' => $this->placement_id,
                'mentor_ids' => $this->mentor_ids,
            ]);

            flash()->success(__('internship.registration_verification.success'));
            $this->showProcessModal = false;
            $this->processId = null;
            $this->placement_id = '';
            $this->mentor_ids = [];
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render(): View
    {
        return <<<'HTML'
        <div>
            <x-mary-header :title="__('internship.registration_verification.title')" :subtitle="__('internship.registration_verification.subtitle')" separator />

            <x-mary-card>
                @if($this->pendingRegistrations->isEmpty())
                    <x-mary-alert :title="__('internship.registration_verification.empty')" :description="__('internship.registration_verification.empty_desc')" icon="o-check-circle" />
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>{{ __('internship.registration_verification.student') }}</th>
                                    <th>{{ __('internship.registration_verification.program') }}</th>
                                    <th>{{ __('internship.registration_verification.documents') }}</th>
                                    <th>{{ __('internship.registration_verification.submitted') }}</th>
                                    <th>{{ __('internship.registration_verification.subtitle') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->pendingRegistrations as $reg)
                                    @php
                                        $total = $reg->documents->count();
                                        $verified = $reg->documents->where('status', 'verified')->count();
                                        $pending = $reg->documents->where('status', 'pending')->count();
                                        $rejected = $reg->documents->where('status', 'rejected')->count();
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-medium">{{ $reg->mentee?->user?->name ?? 'Unknown' }}</div>
                                            <div class="text-xs text-gray-500">{{ $reg->mentee?->user?->email }}</div>
                                        </td>
                                        <td>{{ $reg->internship?->name ?? '-' }}</td>
                                        <td>
                                            @if($total > 0)
                                                <div class="flex gap-2 text-xs">
                                                    <span class="badge badge-success badge-sm">{{ $verified }} {{ __('internship.registration_verification.verified') }}</span>
                                                    <span class="badge badge-warning badge-sm">{{ $pending }} {{ __('internship.registration_verification.pending') }}</span>
                                                    @if($rejected > 0)
                                                        <span class="badge badge-error badge-sm">{{ $rejected }} {{ __('internship.registration_verification.rejected') }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">{{ __('internship.registration_verification.no_docs') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $reg->created_at->diffForHumans() }}</td>
                                        <td>
                                            <x-mary-button :label="__('internship.registration_verification.process')" wire:click="process('{{ $reg->id }}')" icon="o-chevron-right" class="btn-primary btn-sm" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-mary-card>

            <x-mary-modal wire:model="showProcessModal" :title="__('internship.registration_verification.process_title')">
                @if($this->selectedRegistration)
                    <div class="mb-4 p-3 bg-base-200 rounded-box">
                        <p class="font-medium">{{ $this->selectedRegistration->mentee?->user?->name }}</p>
                        <p class="text-sm text-gray-500">{{ $this->selectedRegistration->internship?->name }}</p>
                    </div>

                    <x-mary-form wire:submit="confirmProcess">
                        <x-mary-select
                            :label="__('internship.registration_verification.placement')"
                            wire:model="placement_id"
                            :options="$this->availablePlacements"
                            :placeholder="__('internship.registration_verification.select_placement')"
                            icon="o-briefcase" />

                        <x-mary-select
                            :label="__('internship.registration_verification.assigned_mentors')"
                            wire:model="mentor_ids"
                            :options="$this->mentors"
                            :placeholder="__('internship.registration_verification.select_mentors')"
                            multiple
                            icon="o-user-group" />

                        <x-slot:actions>
                            <x-mary-button :label="__('internship.registration_verification.cancel')" wire:click="$set('showProcessModal', false)" />
                            <x-mary-button :label="__('internship.registration_verification.verify_place')" type="submit" icon="o-check" class="btn-primary" />
                        </x-slot:actions>
                    </x-mary-form>
                @endif
            </x-mary-modal>
        </div>
        HTML;
    }
}
