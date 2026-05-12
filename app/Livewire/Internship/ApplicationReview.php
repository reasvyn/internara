<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Actions\Internship\ApproveAccountApplicationAction;
use App\Actions\Internship\RejectAccountApplicationAction;
use App\Models\AccountApplication;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ApplicationReview extends Component
{
    public ?string $rejectId = null;

    public string $rejectionReason = '';

    public bool $showRejectModal = false;

    #[Computed]
    public function pendingApplications(): Collection
    {
        return AccountApplication::with(['internship', 'school'])
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function approve(string $id, ApproveAccountApplicationAction $action): void
    {
        $action->execute($id, auth()->user());
        flash()->success('Application approved. Account and registration created.');
    }

    public function confirmReject(string $id): void
    {
        $this->rejectId = $id;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject(RejectAccountApplicationAction $action): void
    {
        $this->validate(['rejectionReason' => 'required|string|max:1000']);

        $action->execute($this->rejectId, auth()->user(), $this->rejectionReason);

        flash()->success('Application rejected.');
        $this->showRejectModal = false;
        $this->rejectId = null;
        $this->rejectionReason = '';
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-header title="Account Applications" subtitle="Review and manage pending applications" separator />

            <x-mary-card>
                @if($this->pendingApplications->isEmpty())
                    <x-mary-alert title="No pending applications" description="All applications have been processed." icon="o-check-circle" />
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Program</th>
                                    <th>School</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->pendingApplications as $app)
                                    <tr>
                                        <td>{{ $app->name }}</td>
                                        <td>{{ $app->email }}</td>
                                        <td>{{ $app->internship?->name }}</td>
                                        <td>{{ $app->school?->name ?? '-' }}</td>
                                        <td>{{ $app->created_at->diffForHumans() }}</td>
                                        <td>
                                            <div class="flex gap-2">
                                                <x-mary-button label="Approve" wire:click="approve('{{ $app->id }}')" icon="o-check" class="btn-success btn-sm" />
                                                <x-mary-button label="Reject" wire:click="confirmReject('{{ $app->id }}')" icon="o-x-mark" class="btn-error btn-sm" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-mary-card>

            <x-mary-modal wire:model="showRejectModal" title="Reject Application">
                <x-mary-form wire:submit="reject">
                    <x-mary-textarea label="Rejection Reason" wire:model="rejectionReason" required />
                    <x-slot:actions>
                        <x-mary-button label="Cancel" wire:click="$set('showRejectModal', false)" />
                        <x-mary-button label="Reject" type="submit" icon="o-x-mark" class="btn-error" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-modal>
        </div>
        HTML;
    }
}
