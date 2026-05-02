<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Actions\Internship\SubmitRequirementAction;
use App\Models\InternshipRegistration;
use App\Models\InternshipRequirement;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class RequirementManager extends Component
{
    use Toast, WithFileUploads;

    public InternshipRegistration $registration;

    public array $files = [];

    /**
     * Get active requirements.
     */
    #[Computed]
    public function requirements()
    {
        return InternshipRequirement::where('is_active', true)
            ->with(['submissions' => function ($q) {
                $q->where('registration_id', $this->registration->id);
            }])
            ->get();
    }

    /**
     * Submit a file for a requirement.
     */
    public function submitFile(string $requirementId, SubmitRequirementAction $submitAction)
    {
        $this->validate([
            'files.'.$requirementId => 'required|file|max:5120', // 5MB limit
        ]);

        $submitAction->execute(
            $this->registration,
            $requirementId,
            $this->files[$requirementId]
        );

        $this->success('File submitted successfully.');
        $this->reset('files.'.$requirementId);
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-header title="Internship Requirements" subtitle="Submit mandatory documents for your placement" separator />

            <div class="grid grid-cols-1 gap-4">
                @foreach($this->requirements as $requirement)
                    @php 
                        $submission = $requirement->submissions->first();
                        $status = $submission?->status ?? 'missing';
                    @endphp
                    
                    <x-mary-card 
                        title="{{ $requirement->name }}" 
                        subtitle="{{ $requirement->description }}" 
                        separator>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <x-mary-badge 
                                    :label="str($status)->headline()" 
                                    :class="match($status) {
                                        'verified' => 'badge-success',
                                        'rejected' => 'badge-error',
                                        'pending' => 'badge-warning',
                                        default => 'badge-neutral'
                                    }" />
                                
                                @if($requirement->is_mandatory)
                                    <x-mary-badge label="Mandatory" class="badge-ghost ml-2" />
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if($requirement->type === 'document')
                                    @if($status === 'missing' || $status === 'rejected')
                                        <x-mary-file wire:model="files.{{ $requirement->id }}" label="Upload" class="btn-sm" />
                                        <x-mary-button 
                                            label="Submit" 
                                            wire:click="submitFile('{{ $requirement->id }}')" 
                                            class="btn-primary btn-sm" 
                                            :disabled="!isset($files[$requirement->id])" />
                                    @else
                                        <x-mary-button label="View Submission" icon="o-eye" class="btn-sm btn-ghost" />
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if($submission?->notes)
                            <div class="mt-4 p-3 bg-base-200 rounded text-sm italic">
                                <strong>Feedback:</strong> {{ $submission->notes }}
                            </div>
                        @endif
                    </x-mary-card>
                @endforeach
            </div>
        </div>
        HTML;
    }
}
