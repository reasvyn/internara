<div>
    <x-mary-header :title="__('assessment.rubrics')" :subtitle="__('assessment.rubrics_subtitle')" separator>
        <x-slot:actions>
            <x-mary-button :label="__('assessment.new_rubric')" icon="o-plus" wire:click="addRubric" class="btn-primary" />
        </x-slot:actions>
    </x-mary-header>

    @forelse($this->rubrics as $rubric)
        @php $competencies = $rubric->structure['competencies'] ?? []; @endphp

        <x-mary-card class="mb-4">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold">{{ $rubric->name }}</h3>
                    @if($rubric->description)
                        <p class="text-sm text-base-content/60">{{ $rubric->description }}</p>
                    @endif
                    <div class="flex gap-2 mt-1">
                        @if($rubric->is_active)
                            <span class="badge badge-success badge-sm">{{ __('assessment.active') }}</span>
                        @else
                            <span class="badge badge-ghost badge-sm">{{ __('assessment.inactive') }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-mary-button icon="o-pencil" wire:click="editRubric('{{ $rubric->id }}')" class="btn-sm btn-ghost" />
                    <x-mary-button icon="o-trash" wire:click="askRemoveRubric('{{ $rubric->id }}')" class="btn-sm btn-ghost text-error" />
                </div>
            </div>

            <div class="divider text-xs text-base-content/40 my-2">{{ __('assessment.competencies') }}</div>

            @forelse($competencies as $competency)
                <div class="ml-4 mb-3 p-3 bg-base-200/50 rounded-xl border border-base-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-medium">{{ $competency['name'] }}</p>
                                <span class="badge badge-primary badge-sm">{{ $competency['weight'] }}%</span>
                                <span class="badge badge-ghost badge-sm">{{ \App\Evaluation\Enums\EvaluatorRole::tryFrom($competency['evaluator_role'])?->label() ?? $competency['evaluator_role'] }}</span>
                            </div>
                            @if($competency['description'] ?? null)
                                <p class="text-xs text-base-content/50 mt-1">{{ $competency['description'] }}</p>
                            @endif
                        </div>
                        <div class="flex gap-1">
                            <x-mary-button icon="o-plus" wire:click="addIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}')" class="btn-xs btn-ghost" title="Add Indicator" />
                            <x-mary-button icon="o-pencil" wire:click="editCompetency('{{ $rubric->id }}', '{{ $competency['id'] }}')" class="btn-xs btn-ghost" />
                            <x-mary-button icon="o-trash" wire:click="askRemoveCompetency('{{ $rubric->id }}', '{{ $competency['id'] }}')" class="btn-xs btn-ghost text-error" />
                        </div>
                    </div>

                    @if(!empty($competency['indicators']))
                        <div class="ml-4 mt-2 space-y-1">
                            @foreach($competency['indicators'] as $indicator)
                                <div class="flex items-center justify-between py-1 px-2 bg-base-100 rounded-lg text-sm">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $indicator['name'] }}</span>
                                        <span class="text-xs text-base-content/40">(max {{ $indicator['max_score'] }}, {{ $indicator['weight'] }}%)</span>
                                    </div>
                                    <div class="flex gap-1">
                                        <x-mary-button icon="o-pencil" wire:click="editIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}', '{{ $indicator['id'] }}')" class="btn-xs btn-ghost" />
                                        <x-mary-button icon="o-trash" wire:click="askRemoveIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}', '{{ $indicator['id'] }}')" class="btn-xs btn-ghost text-error" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-4 text-sm text-base-content/40">
                    {{ __('assessment.no_competencies_yet') }}
                    <x-mary-button :label="__('assessment.add_competency')" wire:click="addCompetency('{{ $rubric->id }}')" class="btn-xs btn-primary" />
                </div>
            @endforelse

            @if(!empty($competencies))
                <div class="mt-2">
                    <x-mary-button :label="__('assessment.add_competency')" icon="o-plus" wire:click="addCompetency('{{ $rubric->id }}')" class="btn-sm btn-ghost" />
                </div>
            @endif
        </x-mary-card>
    @empty
        <x-mary-card>
            <div class="text-center py-12 text-base-content/40">
                <x-mary-icon name="o-clipboard-document-list" class="size-16 mx-auto mb-4 opacity-30" />
                <p class="text-lg font-medium">{{ __('assessment.no_rubrics') }}</p>
                <p class="text-sm">{{ __('assessment.rubrics_subtitle') }}</p>
            </div>
        </x-mary-card>
    @endforelse

    <x-mary-modal wire:model="rubricModal" title="{{ $rubricForm['id'] ? 'Edit Rubric' : 'New Rubric' }}" separator class="backdrop-blur-sm">
        <x-mary-form wire:submit="saveRubric">
            <x-mary-input :label="__('common.name')" wire:model="rubricForm.name" required />
            <x-mary-textarea :label="__('common.description')" wire:model="rubricForm.description" />
            <x-mary-checkbox :label="__('assessment.active')" wire:model="rubricForm.is_active" />
            <x-slot:actions>
                <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('rubricModal', false)" />
                <x-mary-button :label="__('common.actions.save')" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="competencyModal" title="{{ $competencyForm['id'] ? 'Edit Competency' : 'New Competency' }}" separator class="backdrop-blur-sm">
        <x-mary-form wire:submit="saveCompetency">
            <x-mary-input :label="__('common.name')" wire:model="competencyForm.name" required />
            <x-mary-textarea :label="__('common.description')" wire:model="competencyForm.description" />
            <div class="grid grid-cols-2 gap-4">
                <x-mary-input :label="__('assessment.weight')" wire:model="competencyForm.weight" type="number" min="0" max="100" required />
                <x-mary-input :label="__('assessment.order')" wire:model="competencyForm.order" type="number" min="0" required />
            </div>
            <x-mary-select :label="__('assessment.evaluator_role')" wire:model="competencyForm.evaluator_role" :options="$this->evaluatorRoles" placeholder="Select role" required />
            <x-slot:actions>
                <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('competencyModal', false)" />
                <x-mary-button :label="__('common.actions.save')" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="indicatorModal" title="{{ $indicatorForm['id'] ? 'Edit Indicator' : 'New Indicator' }}" separator class="backdrop-blur-sm">
        <x-mary-form wire:submit="saveIndicator">
            <x-mary-input :label="__('common.name')" wire:model="indicatorForm.name" required />
            <x-mary-textarea :label="__('common.description')" wire:model="indicatorForm.description" />
            <div class="grid grid-cols-3 gap-4">
                <x-mary-input :label="__('assessment.max_score')" wire:model="indicatorForm.max_score" type="number" min="1" required />
                <x-mary-input :label="__('assessment.weight')" wire:model="indicatorForm.weight" type="number" min="0" max="100" required />
                <x-mary-input :label="__('assessment.order')" wire:model="indicatorForm.order" type="number" min="0" required />
            </div>
            <x-slot:actions>
                <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('indicatorModal', false)" />
                <x-mary-button :label="__('common.actions.save')" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-core::ui.confirm :message="$confirmMessage" />
</div>

@include('assessment.rubric.components.rubric-guide')
