<div class="p-8">
    <x-mary-header title="{{ __('logbook.assessment_title') }}" subtitle="{{ __('logbook.assessment_subtitle') }}" separator progress-indicator />

    {{-- Registration selection --}}
    @if($registrations->count() > 1)
        <x-mary-card class="mb-6">
            <div class="space-y-3">
                <p class="text-sm font-semibold">{{ __('logbook.assessment_select_student') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($registrations as $reg)
                        <x-mary-button
                            :label="$reg->mentee->user->name"
                            wire:click="selectRegistration('{{ $reg->id }}')"
                            :class="$registrationId === $reg->id ? 'btn-primary' : 'btn-outline'"
                            class="btn-sm rounded-xl"
                        />
                    @endforeach
                </div>
            </div>
        </x-mary-card>
    @endif

    @if($showForm && $registrationId)
        <x-mary-card>
            <x-mary-form wire:submit="save" class="space-y-6">
                @php
                    $reg = $registrations->firstWhere('id', $registrationId);
                @endphp
                @if($reg)
                    <div class="bg-base-200/30 rounded-xl p-4">
                        <p class="text-sm font-bold">{{ $reg->mentee->user->name }}</p>
                        <p class="text-xs text-base-content/50">{{ $reg->mentee->user->email }}</p>
                    </div>
                @endif

                {{-- Rubric table --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold">{{ __('logbook.assessment_rubric') }}</p>
                        <x-mary-button icon="o-plus" class="btn-ghost btn-sm" wire:click="addCriterion" :label="__('logbook.assessment_add_criterion')" />
                    </div>

                    @if($rubric === [])
                        <p class="text-sm text-base-content/40 italic py-4 text-center">{{ __('logbook.assessment_no_criteria') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-xs">{{ __('logbook.assessment_criterion') }}</th>
                                        <th class="text-xs w-20">{{ __('logbook.assessment_weight') }}</th>
                                        <th class="text-xs w-20">{{ __('logbook.assessment_score') }}</th>
                                        <th class="w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rubric as $index => $criterion)
                                        <tr>
                                            <td>
                                                <x-mary-input wire:model="rubric.{{ $index }}.criterion" placeholder="e.g. Discipline" class="input-sm" />
                                            </td>
                                            <td>
                                                <x-mary-input wire:model="rubric.{{ $index }}.weight" type="number" min="0" max="100" class="input-sm text-center" />
                                            </td>
                                            <td>
                                                <x-mary-input wire:model="rubric.{{ $index }}.score" type="number" min="0" max="100" class="input-sm text-center" />
                                            </td>
                                            <td>
                                                <x-mary-button icon="o-trash" class="btn-ghost btn-xs text-error" wire:click="removeCriterion({{ $index }})" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <x-mary-textarea :label="__('logbook.assessment_notes')" wire:model="notes" rows="3" />

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" class="btn-ghost" type="reset" />
                    <x-mary-button :label="__('logbook.assessment_submit')" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-card>
    @endif

    {{-- Previous submissions summary --}}
    @if($assessments->isNotEmpty())
        <x-mary-card class="mt-6">
            <p class="text-sm font-semibold mb-3">{{ __('logbook.assessment_previous') }}</p>
            <div class="space-y-2">
                @foreach($assessments as $assessment)
                    <div class="flex items-center justify-between p-3 bg-base-200/30 rounded-xl">
                        <div>
                            <p class="text-sm font-medium">{{ $assessment->registration->mentee->user->name }}</p>
                            <p class="text-xs text-base-content/50">{{ $assessment->submitted_at?->format('d M Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            @if($assessment->score)
                                <p class="text-lg font-bold text-primary">{{ $assessment->score }}</p>
                            @else
                                <span class="text-xs text-base-content/40 italic">{{ __('logbook.assessment_not_submitted') }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-mary-card>
    @endif
</div>
