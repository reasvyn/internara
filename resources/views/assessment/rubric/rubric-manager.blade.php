<x-ui::components.record-manager :title="__('assessment.rubrics')" :subtitle="__('assessment.rubrics_subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('assessment.new_rubric')" icon="plus" wire:click="addRubric" color="primary" sm />
    </x-slot:headerActions>

    <div class="space-y-4 p-4">
        @forelse ($this->rows() as $rubric)
            @php $competencies = $rubric->structure['competencies'] ?? []; @endphp

            <x-ts-card shadowless class="border-base-content/10 border">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-bold">{{ $rubric->name }}</h3>
                        @if ($rubric->description)
                            <p class="text-base-content/60 text-xs">{{ $rubric->description }}</p>
                        @endif
                        <div class="mt-1 flex gap-2">
                            @if ($rubric->is_active)
                                <x-ts-badge :text="__('assessment.active')" color="green" xs />
                            @else
                                <x-ts-badge :text="__('assessment.inactive')" color="gray" xs />
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-1.5">
                        <x-ui::components.table-action action="edit" wire:click="editRubric('{{ $rubric->id }}')" />
                        <x-ui::components.table-action
                            action="delete"
                            wire:click="askRemoveRubric('{{ $rubric->id }}')"
                        />
                    </div>
                </div>

                <div class="divider text-base-content/40 my-2 text-xs">{{ __('assessment.competencies') }}</div>

                @forelse ($competencies as $competency)
                    <div class="bg-base-200/50 border-base-content/10 mb-3 rounded-xl border p-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold">{{ $competency['name'] }}</p>
                                    <x-ts-badge :text="$competency['weight'].'%'" color="primary" xs />
                                    <x-ts-badge
                                        :text="\App\Modules\Assessment\Enums\EvaluatorRole::tryFrom($competency['evaluator_role'])?->label() ?? $competency['evaluator_role']"
                                        color="gray"
                                        xs
                                    />
                                </div>
                                @if ($competency['description'] ?? null)
                                    <p class="text-base-content/50 mt-1 text-xs">{{ $competency['description'] }}</p>
                                @endif
                            </div>
                            <div class="flex gap-1">
                                <x-ui::components.table-action
                                    action="custom"
                                    icon="plus"
                                    color="secondary"
                                    wire:click="addIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}')"
                                    :label="__('assessment.add_indicator')"
                                />
                                <x-ui::components.table-action
                                    action="edit"
                                    wire:click="editCompetency('{{ $rubric->id }}', '{{ $competency['id'] }}')"
                                />
                                <x-ui::components.table-action
                                    action="delete"
                                    wire:click="askRemoveCompetency('{{ $rubric->id }}', '{{ $competency['id'] }}')"
                                />
                            </div>
                        </div>

                        @if (! empty($competency['indicators']))
                            <div class="mt-2 ml-4 space-y-1">
                                @foreach ($competency['indicators'] as $indicator)
                                    <div class="bg-base-100 border-base-content/10 flex items-center justify-between rounded-lg border px-3 py-1.5 text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ $indicator['name'] }}</span>
                                            <span class="text-base-content/40">(max {{ $indicator['max_score'] }}, {{ $indicator['weight'] }}%)</span>
                                        </div>
                                        <div class="flex gap-1">
                                            <x-ui::components.table-action
                                                action="edit"
                                                wire:click="editIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}', '{{ $indicator['id'] }}')"
                                            />
                                            <x-ui::components.table-action
                                                action="delete"
                                                wire:click="askRemoveIndicator('{{ $rubric->id }}', '{{ $competency['id'] }}', '{{ $indicator['id'] }}')"
                                            />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-base-content/40 py-4 text-center text-xs">
                        {{ __('assessment.no_competencies_yet') }}
                        <x-ts-button
                            :text="__('assessment.add_competency')"
                            wire:click="addCompetency('{{ $rubric->id }}')"
                            color="primary"
                            xs
                            class="ml-2"
                        />
                    </div>
                @endforelse

                @if (! empty($competencies))
                    <div class="mt-2">
                        <x-ts-button
                            :text="__('assessment.add_competency')"
                            icon="plus"
                            wire:click="addCompetency('{{ $rubric->id }}')"
                            color="secondary"
                            flat
                            xs
                        />
                    </div>
                @endif
            </x-ts-card>
        @empty
            <x-ui::widgets.empty-state
                icon="clipboard-document-list"
                :title="__('assessment.no_rubrics')"
                :description="__('assessment.rubrics_subtitle')"
            />
        @endforelse

        @if ($this->rows()->hasPages())
            <div class="border-base-content/10 border-t pt-4">{{ $this->rows()->links() }}</div>
        @endif
    </div>

    <x-slot:modal>
        <x-ts-modal
            wire="rubricModal"
            :title="$rubricForm['id'] ? __('assessment.edit_rubric') : __('assessment.new_rubric')"
            separator
            blur
        >
            <form wire:submit="saveRubric" class="space-y-4">
                <x-ts-input :label="__('common.name')" wire:model="rubricForm.name" required />
                <x-ts-textarea :label="__('common.description')" wire:model="rubricForm.description" />
                <x-ts-checkbox :label="__('assessment.active')" wire:model="rubricForm.is_active" />
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('rubricModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" type="submit" color="primary" sm />
                </div>
            </form>
        </x-ts-modal>

        <x-ts-modal
            wire="competencyModal"
            :title="$competencyForm['id'] ? __('assessment.edit_competency') : __('assessment.new_competency')"
            separator
            blur
        >
            <form wire:submit="saveCompetency" class="space-y-4">
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
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('competencyModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" type="submit" color="primary" sm />
                </div>
            </form>
        </x-ts-modal>

        <x-ts-modal
            wire="indicatorModal"
            :title="$indicatorForm['id'] ? __('assessment.edit_indicator') : __('assessment.new_indicator')"
            separator
            blur
        >
            <form wire:submit="saveIndicator" class="space-y-4">
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
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('indicatorModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" type="submit" color="primary" sm />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>

    <x-slot:auxiliary>
        @include('assessment.rubric.components.rubric-guide')
    </x-slot:auxiliary>
</x-ui::components.record-manager>
