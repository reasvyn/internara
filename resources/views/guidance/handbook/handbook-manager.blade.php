<x-core::ui.record-manager
    :title="__('guidance.title')"
    :subtitle="__('guidance.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('guidance.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:filters>
        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('guidance.target_audience') }}</label>
        <select wire:model.live="filters.target_audience" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="all">{{ __('guidance.audience_all') }}</option>
            <option value="student">{{ __('guidance.audience_student') }}</option>
            <option value="teacher">{{ __('guidance.audience_teacher') }}</option>
            <option value="supervisor">{{ __('guidance.audience_supervisor') }}</option>
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('guidance.status') }}</label>
        <select wire:model.live="filters.is_active" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="1">{{ __('guidance.active') }}</option>
            <option value="0">{{ __('guidance.inactive') }}</option>
        </select>
    </x-slot:filters>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_author.name', $handbook)
                <span class="text-sm">{{ $handbook->author?->name ?? '—' }}</span>
            @endscope

            @scope('cell_is_active', $handbook)
                <x-mary-badge :value="$handbook->is_active ? __('guidance.active') : __('guidance.inactive')"
                    :class="$handbook->is_active ? 'badge-success' : 'badge-ghost'" />
            @endscope

            @scope('actions', $handbook)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm btn-circle"
                        wire:click="edit('{{ $handbook->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm btn-circle text-error"
                        wire:click="delete('{{ $handbook->id }}')"
                        wire:confirm="{{ __('common.actions.confirm_action') }}" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('guidance.edit') : __('guidance.create')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="store" class="space-y-4">
                <div class="space-y-4">
                    <x-mary-input :label="__('guidance.title_field')" wire:model="form.title" />
                    <x-mary-textarea :label="__('guidance.content_field')" wire:model="form.content" rows="8" />
                    <x-mary-input :label="__('guidance.version_field')" type="number" wire:model="form.version" />
                    <x-mary-select :label="__('guidance.target_audience')" wire:model="form.target_audience"
                        :options="[
                            ['id' => 'all', 'name' => __('guidance.audience_all')],
                            ['id' => 'student', 'name' => __('guidance.audience_student')],
                            ['id' => 'teacher', 'name' => __('guidance.audience_teacher')],
                            ['id' => 'supervisor', 'name' => __('guidance.audience_supervisor')],
                        ]" />
                    <x-mary-file wire:model="file" :label="__('guidance.file')" accept="application/pdf" />
                    @if($form->id)
                        <x-mary-toggle :label="__('guidance.remove_file')" wire:model="removeFile" />
                    @endif
                    <x-mary-toggle :label="__('guidance.active')" wire:model="form.is_active" />
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost" />
                    <x-mary-button :label="__('common.actions.save')" wire:click="store" class="btn-primary" spinner="store" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
