<div>
    @if ($registration)
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight">{{ __('registration.preparation_title') }}</h1>
            <p class="text-base-content/70 text-sm">
                {{ __('registration.preparation_subtitle') }} ({{ $registration->internship?->name }})
            </p>
        </div>

        <ul class="steps steps-vertical lg:steps-horizontal mb-8 w-full" role="tablist">
            <li class="step {{ $step >= 1 ? 'step-primary' : '' }}" wire:click="goToStep(1)">
                {{ __('registration.wizard.step_placement') }}
            </li>
            <li class="step {{ $step >= 2 ? 'step-primary' : '' }}" wire:click="goToStep(2)">
                {{ __('registration.documents') }}
            </li>
            <li class="step {{ $step >= 3 ? 'step-primary' : '' }}" wire:click="goToStep(3)">
                {{ __('registration.verification.status') }}
            </li>
        </ul>

        <x-ts-card shadowless class="border-base-content/10 border">
            @if ($step === 1)
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">{{ __('registration.placement_choice_title') }}</h2>
                        <p class="text-base-content/70 text-sm">{{ __('registration.placement_choice_desc') }}</p>
                    </div>

                    <div class="flex gap-4">
                        <button
                            type="button"
                            wire:click="setPlacementType('partner')"
                            class="btn flex-1 {{ $placementType === 'partner' ? 'btn-primary' : 'btn-outline' }}"
                        >
                            {{ __('registration.partner_option') }}
                        </button>
                        <button
                            type="button"
                            wire:click="setPlacementType('proposed')"
                            class="btn flex-1 {{ $placementType === 'proposed' ? 'btn-primary' : 'btn-outline' }}"
                        >
                            {{ __('registration.proposed_option') }}
                        </button>
                    </div>

                    @if ($placementType === 'partner')
                        <div class="space-y-4">
                            <label for="placement-select" class="block text-sm font-medium">
                                {{ __('registration.verification.placement') }}
                            </label>
                            <select
                                id="placement-select"
                                wire:model="placementId"
                                class="select select-bordered w-full"
                            >
                                <option value="">{{ __('registration.verification.select_placement') }}</option>
                                @foreach ($this->availablePlacements as $placement)
                                    <option value="{{ $placement->id }}">
                                        {{ $placement->company?->name ?? $placement->name }} ({{ __('registration.quota_remaining', ['slots' => $placement->availableSlots()]) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('placementId')
                                <p class="text-error text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="space-y-4">
                            <x-ts-input
                                :label="__('registration.wizard.proposed_company')"
                                wire:model="proposedCompanyName"
                                placeholder="PT Contoh Industri Digital"
                            />
                            <x-ts-textarea
                                :label="__('registration.wizard.proposed_address')"
                                wire:model="proposedCompanyAddress"
                                placeholder="Jl. Raya Industri KM 10, Kawasan Industri"
                            />
                        </div>
                    @endif

                    <div class="mt-6 flex justify-end">
                        <x-ts-button
                            wire:click="savePlacementPreference"
                            wire:loading.attr="disabled"
                            :text="__('common.save_and_continue')"
                            color="primary"
                        />
                    </div>
                </div>

            @elseif ($step === 2)
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">{{ __('registration.doc_upload_title') }}</h2>
                        <p class="text-base-content/70 text-sm">{{ __('registration.doc_upload_desc') }}</p>
                    </div>

                    @if ($this->requiredDocuments->isNotEmpty())
                        <div class="space-y-4">
                            @foreach ($this->requiredDocuments as $document)
                                @php
                                    $existing = $this->submittedDocuments->firstWhere('document_id', $document->id);
                                @endphp
                                <div class="bg-base-200/40 border-base-content/10 flex items-start justify-between rounded-xl border p-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $document->title }}</p>
                                        @if ($existing)
                                            <span class="badge badge-success badge-sm mt-1">
                                                {{ __('registration.doc_uploaded') }} ({{ $existing->status->label() }})
                                            </span>
                                        @else
                                            <input
                                                type="file"
                                                wire:model="uploads.{{ $document->id }}"
                                                aria-label="{{ $document->title }}"
                                                class="file-input file-input-bordered file-input-sm mt-2 w-full max-w-xs"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                            />
                                            @error("uploads.{$document->id}")
                                                <p class="text-error mt-1 text-xs">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex justify-between">
                            <x-ts-button
                                wire:click="goToStep(1)"
                                :text="__('registration.wizard.previous')"
                                color="slate"
                                outline
                            />
                            <x-ts-button
                                wire:click="uploadDocuments"
                                wire:loading.attr="disabled"
                                :text="__('common.upload_and_continue')"
                                color="primary"
                            />
                        </div>
                    @else
                        <div class="text-base-content/60 py-8 text-center text-sm">
                            {{ __('registration.doc_no_requirements') }}
                        </div>
                        <div class="mt-6 flex justify-between">
                            <x-ts-button
                                wire:click="goToStep(1)"
                                :text="__('registration.wizard.previous')"
                                color="slate"
                                outline
                            />
                            <x-ts-button
                                wire:click="goToStep(3)"
                                :text="__('registration.wizard.next')"
                                color="primary"
                            />
                        </div>
                    @endif
                </div>

            @elseif ($step === 3)
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">{{ __('registration.status_overview_title') }}</h2>
                        <p class="text-base-content/70 text-sm">{{ __('registration.status_overview_desc') }}</p>
                    </div>

                    <div class="bg-base-200/50 space-y-3 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ __('registration.verification.status') }}:</span>
                            <span class="badge badge-primary">{{ $registration->asRegistrationState()->status()?->label() ?? $registration->status }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ __('registration.wizard.label_placement') }}:</span>
                            <span class="text-sm font-semibold">
                                {{ $registration->placement?->company?->name ?? $registration->proposed_company_details['company_name'] ?? '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ __('common.dates') }}:</span>
                            <span class="text-sm">
                                {{ $registration->start_date?->format('d M Y') ?? '-' }} — {{ $registration->end_date?->format('d M Y') ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-start">
                        <x-ts-button
                            wire:click="goToStep(2)"
                            :text="__('registration.wizard.previous')"
                            color="slate"
                            outline
                        />
                    </div>
                </div>
            @endif
        </x-ts-card>
    @else
        <div class="text-base-content/60 py-16 text-center">
            <x-ts-icon name="document-text" class="mx-auto mb-3 size-16" />
            <h2 class="text-lg font-semibold">{{ __('registration.no_cohort_title') }}</h2>
            <p class="text-base-content/70 mx-auto mt-1 max-w-md text-sm">{{ __('registration.no_cohort_desc') }}</p>
        </div>
    @endif
</div>
