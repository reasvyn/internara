<div>
    <x-ui::main title="{{ __('Internship Deliverables') }}" subtitle="{{ __('Upload mandatory artifacts to complete your internship program.') }}">
        
        @if(!$registrationId)
            <x-ui::alert icon="tabler.info-circle" title="{{ __('No Active Registration') }}" class="alert-warning">
                {{ __('You do not have an active internship registration. Deliverable submission is only available for active interns.') }}
            </x-ui::alert>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Report Upload --}}
                <x-ui::card title="{{ __('PKL Report (PDF)') }}">
                    <div class="mb-4">
                        @php $report = $this->deliverables->firstWhere('type', 'report'); @endphp
                        @if($report)
                            <div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <x-ui::button icon="tabler.file-type-pdf" class="btn-ghost btn-sm text-error" />
                                    <div>
                                        <div class="text-sm font-bold">{{ __('Laporan_PKL.pdf') }}</div>
                                        <div class="text-xs opacity-70">{{ __('Status:') }} {{ $report->getStatusLabel() }}</div>
                                    </div>
                                </div>
                                <x-ui::button label="{{ __('View') }}" link="{{ $report->getFirstMediaUrl('file') }}" target="_blank" class="btn-ghost btn-sm" />
                            </div>
                        @else
                            <p class="text-sm opacity-70 italic">{{ __('No report submitted yet.') }}</p>
                        @endif
                    </div>

                    <x-ui::form wire:submit="submitReport">
                        <x-ui::input type="file" wire:model="reportFile" accept=".pdf" label="{{ __('Select PDF File') }}" required />
                        <x-slot:actions>
                            <x-ui::button label="{{ __('Upload Report') }}" type="submit" class="btn-primary w-full" spinner="submitReport" />
                        </x-slot:actions>
                    </x-ui::form>
                </x-ui::card>

                {{-- Presentation Upload --}}
                <x-ui::card title="{{ __('Presentation Material (PPT/PDF)') }}">
                    <div class="mb-4">
                        @php $presentation = $this->deliverables->firstWhere('type', 'presentation'); @endphp
                        @if($presentation)
                            <div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <x-ui::button icon="tabler.presentation" class="btn-ghost btn-sm text-info" />
                                    <div>
                                        <div class="text-sm font-bold">{{ __('Presentasi_PKL.pdf') }}</div>
                                        <div class="text-xs opacity-70">{{ __('Status:') }} {{ $presentation->getStatusLabel() }}</div>
                                    </div>
                                </div>
                                <x-ui::button label="{{ __('View') }}" link="{{ $presentation->getFirstMediaUrl('file') }}" target="_blank" class="btn-ghost btn-sm" />
                            </div>
                        @else
                            <p class="text-sm opacity-70 italic">{{ __('No presentation submitted yet.') }}</p>
                        @endif
                    </div>

                    <x-ui::form wire:submit="submitPresentation">
                        <x-ui::input type="file" wire:model="presentationFile" accept=".pdf,.ppt,.pptx" label="{{ __('Select PPT/PDF File') }}" required />
                        <x-slot:actions>
                            <x-ui::button label="{{ __('Upload Presentation') }}" type="submit" class="btn-primary w-full" spinner="submitPresentation" />
                        </x-slot:actions>
                    </x-ui::form>
                </x-ui::card>
            </div>

            {{-- Completion Notice --}}
            @if($this->deliverables->where('type', 'report')->first()?->currentStatus('verified')->exists() && $this->deliverables->where('type', 'presentation')->first()?->currentStatus('verified')->exists())
                <div class="mt-6">
                    <x-ui::alert icon="tabler.certificate" title="{{ __('Requirements Met') }}" class="alert-success">
                        {{ __('Both your report and presentation have been verified. You have fulfilled the mandatory completion requirements.') }}
                    </x-ui::alert>
                </div>
            @endif
        @endif
    </x-ui::main>
</div>
