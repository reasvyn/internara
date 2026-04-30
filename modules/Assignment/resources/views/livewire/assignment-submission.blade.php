<div>
    <x-ui::main title="{{ __('assignment::ui.assignments') }}" subtitle="{{ __('Complete your mandatory tasks to finish the program.') }}">
        
        @if(!$registrationId)
            <x-ui::alert icon="tabler.info-circle" title="{{ __('No Active Registration') }}" class="alert-warning">
                {{ __('You do not have an active internship registration. Assignment submission is only available for active interns.') }}
            </x-ui::alert>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($this->assignments as $assignment)
                    <x-ui::card :title="$assignment->title">
                        <x-slot:subtitle>
                            @if($assignment->is_mandatory)
                                <x-ui::badge label="{{ __('Mandatory') }}" class="badge-error badge-sm" />
                            @endif
                            <span class="text-xs opacity-70">{{ $assignment->description }}</span>
                        </x-slot:subtitle>

                        <div class="mb-4">
                            @php 
                                $submission = $assignment->submissions()
                                    ->where('registration_id', $registrationId)
                                    ->latest()
                                    ->first(); 
                            @endphp

                            @if($submission)
                                <div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        @if($assignment->type->slug === 'laporan-pkl' || $assignment->type->slug === 'presentasi-pkl')
                                            <x-ui::button icon="tabler.file-check" class="btn-ghost btn-sm text-success" />
                                        @else
                                            <x-ui::button icon="tabler.message-check" class="btn-ghost btn-sm text-success" />
                                        @endif
                                        <div>
                                            <div class="text-sm font-bold">{{ __('assignment::ui.submitted_on') }} {{ $submission->submitted_at?->format('d/m/Y H:i') }}</div>
                                            <div class="text-xs">
                                                {{ __('Status:') }} 
                                                <x-ui::badge :label="$submission->getStatusLabel()" :class="'badge-sm ' . $submission->getStatusColor()" />
                                            </div>
                                        </div>
                                    </div>
                                    @if($submission->getFirstMediaUrl('file'))
                                        <x-ui::button label="{{ __('assignment::ui.view_file') }}" link="{{ $submission->getFirstMediaUrl('file') }}" target="_blank" class="btn-ghost btn-sm" />
                                    @endif
                                </div>
                            @else
                                <p class="text-sm opacity-70 italic">{{ __('assignment::ui.no_submission') }}</p>
                            @endif
                        </div>

                        <x-ui::form wire:submit="submit('{{ $assignment->id }}')">
                            @if($assignment->type->slug === 'laporan-pkl' || $assignment->type->slug === 'presentasi-pkl')
                                <x-ui::input type="file" wire:model="uploads.{{ $assignment->id }}" label="{{ __('Upload File') }}" required />
                            @else
                                <x-ui::textarea wire:model="contents.{{ $assignment->id }}" label="{{ __('Your Response') }}" placeholder="{{ __('Enter your work here...') }}" required />
                            @endif

                            <x-slot:actions>
                                <x-ui::button label="{{ __('assignment::ui.submit_assignment') }}" type="submit" class="btn-primary w-full" spinner="submit('{{ $assignment->id }}')" />
                            </x-slot:actions>
                        </x-ui::form>
                    </x-ui::card>
                @endforeach
            </div>

            {{-- Completion Status --}}
            @if(app(\Modules\Assignment\Services\Contracts\AssignmentService::class)->isFulfillmentComplete($registrationId))
                <div class="mt-6">
                    <x-ui::alert icon="tabler.certificate" title="{{ __('All Requirements Met') }}" class="alert-success">
                        {{ __('All your mandatory assignments have been verified. You have fulfilled the program completion requirements.') }}
                    </x-ui::alert>
                </div>
            @endif
        @endif
    </x-ui::main>
</div>
