<div>
    <x-ui::components.page-header :title="__('assessment.rubrics')" :description="__('assessment.rubrics_subtitle')">
        <x-slot:actions>
            <x-ts-button :text="__('assessment.new_rubric')" icon="plus" wire:click="addRubric" color="primary" />
        </x-slot:actions>
    </x-ui::components.page-header>

    @forelse ($this->rubrics as $rubric)
        @php $competencies = $rubric->structure['competencies'] ?? []; @endphp

        <x-ts-card shadowless class="mb-4">
            <div class="mb-4 flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold">{{ $rubric->name }}</h3>
                    @if ($rubric->description)
                        <p class="text-base-content/60 text-sm">{{ $rubric->description }}</p>
                    @endif
                    <div class="mt-1 flex gap-2">
                        @if ($rubric->is_active)
                            <x-ts-badge :text="__('assessment.active')" color="green" xs />
                        @else
                            <x-ts-badge :text="__('assessment.inactive')" color="white" xs />
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-ts-button.circle
                        aria-label="{{ __('common.actions.edit') }}"
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="editRubric('{{ $rubric->id }}')"
                    />
                    <x-ts-button.circle
                        aria-label="{{ __('common.actions.delete') }}"
                        icon="trash"
                        color="red"
                        sm
                        wire:click="askRemoveRubric('{{ $rubric->id }}')"
                    />
                </div>
            </div>

            <div class="divider text-base-content/40 my-2 text-xs">{{ __('assessment.competencies') }}</div>

            @forelse ($competencies as $competency)
                <div class="bg-base-200/50 border-base-200 mb-3 ml-4 rounded-xl border p-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-medium">{{ $competency['name'] }}</p>
                                <x-ts-badge :text="$competency['weight'].'%'" color="primary" xs />
                                <x-ts-badge
                                    :text="\App\Modules\Evaluation\Enums\EvaluatorRole::tryFrom($competency['evaluator_role'])?->label() ?? $competency['evaluator_role']"
                                    color="white"
                                    xs
                                />
                            </div>
                            @if ($competency['description'] ?? null)
                                <p class="text-base-content/50 mt-1 text-xs">{{ $competency['description'] }}</p>
                            @endif
                        </div>
                        <div class="flex gap-1">
                            <x-ts-button
                                aria-label="{{ __('assessment.add_indicator') }}"
                                icon="plus"
                                wire:click="addIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}')"
                                class="btn-xs btn-ghost"
                                :title="__('assessment.add_indicator')"
                            />
                            <x-ts-button
                                aria-label="{{ __('common.actions.edit') }}"
                                icon="pencil"
                                wire:click="editCompetency('{{ $rubric->id }}', '{{ $competency['id'] }}')"
                                class="btn-xs btn-ghost"
                            />
                            <x-ts-button
                                aria-label="{{ __('common.actions.delete') }}"
                                icon="trash"
                                wire:click="askRemoveCompetency('{{ $rubric->id }}', '{{ $competency['id'] }}')"
                                class="btn-xs btn-ghost text-error"
                            />
                        </div>
                    </div>

                    @if (! empty($competency['indicators']))
                        <div class="mt-2 ml-4 space-y-1">
                            @foreach ($competency['indicators'] as $indicator)
                                <div class="bg-base-100 flex items-center justify-between rounded-lg px-2 py-1 text-sm">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $indicator['name'] }}</span>
                                        <span class="text-base-content/40 text-xs">(max {{ $indicator['max_score'] }}, {{ $indicator['weight'] }}%)</span>
                                    </div>
                                    <div class="flex gap-1">
                                        <x-ts-button
                                            aria-label="{{ __('common.actions.edit') }}"
                                            icon="pencil"
                                            wire:click="editIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}', '{{ $indicator['id'] }}')"
                                            class="btn-xs btn-ghost"
                                        />
                                        <x-ts-button
                                            aria-label="{{ __('common.actions.delete') }}"
                                            icon="trash"
                                            wire:click="askRemoveIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}', '{{ $indicator['id'] }}')"
                                            class="btn-xs btn-ghost text-error"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-base-content/40 py-4 text-center text-sm">
                    {{ __('assessment.no_competencies_yet') }}
                    <x-ts-button
                        :text="__('assessment.add_competency')"
                        wire:click="addCompetency('{{ $rubric->id }}')"
                        class="btn-xs btn-primary"
                    />
                </div>
            @endforelse

            @if (! empty($competencies))
                <div class="mt-2">
                    <x-ts-button
                        :text="__('assessment.add_competency')"
                        icon="plus"
                        wire:click="addCompetency('{{ $rubric->id }}')"
                        class="btn-sm btn-ghost"
                    />
                </div>
            @endif
        </x-ts-card>

    @empty
        <x-ts-card shadowless>
            <div class="text-base-content/40 py-12 text-center">
                <x-ts-icon name="clipboard-document-list" class="mx-auto mb-4 size-16 opacity-30" />
                <p class="text-lg font-medium">{{ __('assessment.no_rubrics') }}</p>
                <p class="text-sm">{{ __('assessment.rubrics_subtitle') }}</p>
            </div>
        </x-ts-card>

    @endforelse

    <x-ts-modal
        wire="rubricModal"
        :title="$rubricForm['id'] ? __('assessment.edit_rubric') : __('assessment.new_rubric')"
        separator
        blur
    >
        <form wire:submit="saveRubric">
            <x-ts-input :label="__('common.name')" wire:model="rubricForm.name" required />
            <x-ts-textarea :label="__('common.description')" wire:model="rubricForm.description" />
            <x-ts-checkbox :label="__('assessment.active')" wire:model="rubricForm.is_active" />
            <x-slot:actions>
                <x-ts-button :text="__('common.actions.cancel')" wire:click="$set('rubricModal', false)" />
                <x-ts-button :text="__('common.actions.save')" type="submit" icon="check" color="primary" />
            </x-slot:actions>
        </form>
    </x-ts-modal>

    <x-ts-modal
        wire="competencyModal"
        :title="$competencyForm['id'] ? __('assessment.edit_competency') : __('assessment.new_competency')"
        separator
        blur
    >
        <form wire:submit="saveCompetency">
            <x-ts-input :label="__('common.name')" wire:model="competencyForm.name" required />
            <x-ts-textarea :label="__('common.description')" wire:model="competencyForm.description" />
            <div class="grid grid-cols-2 gap-4">
                <x-ts-input
                    :label="__('assessment.weight')"
                    wire:model="competencyForm.weight"
                    type="number"
                    min="0"
                    max="100"
                    required
                />
                <x-ts-input
                    :label="__('assessment.order')"
                    wire:model="competencyForm.order"
                    type="number"
                    min="0"
                    required
                />
            </div>
            <x-ts-select.native
                :label="__('assessment.evaluator_role')"
                wire:model="competencyForm.evaluator_role"
                :options="ts_options($this->evaluatorRoles, __('assessment.select_role'))"
                required
            />
            <x-slot:actions>
                <x-ts-button :text="__('common.actions.cancel')" wire:click="$set('competencyModal', false)" />
                <x-ts-button :text="__('common.actions.save')" type="submit" icon="check" color="primary" />
            </x-slot:actions>
        </form>
    </x-ts-modal>

    <x-ts-modal
        wire="indicatorModal"
        :title="$indicatorForm['id'] ? __('assessment.edit_indicator') : __('assessment.new_indicator')"
        separator
        blur
    >
        <form wire:submit="saveIndicator">
            <x-ts-input :label="__('common.name')" wire:model="indicatorForm.name" required />
            <x-ts-textarea :label="__('common.description')" wire:model="indicatorForm.description" />
            <div class="grid grid-cols-3 gap-4">
                <x-ts-input
                    :label="__('assessment.max_score')"
                    wire:model="indicatorForm.max_score"
                    type="number"
                    min="1"
                    required
                />
                <x-ts-input
                    :label="__('assessment.weight')"
                    wire:model="indicatorForm.weight"
                    type="number"
                    min="0"
                    max="100"
                    required
                />
                <x-ts-input
                    :label="__('assessment.order')"
                    wire:model="indicatorForm.order"
                    type="number"
                    min="0"
                    required
                />
            </div>
            <x-slot:actions>
                <x-ts-button :text="__('common.actions.cancel')" wire:click="$set('indicatorModal', false)" />
                <x-ts-button :text="__('common.actions.save')" type="submit" icon="check" color="primary" />
            </x-slot:actions>
        </form>
    </x-ts-modal>

    @include('assessment.rubric.components.rubric-guide')
</div>
