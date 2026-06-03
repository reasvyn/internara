<x-core::ui.record-manager
    :title="__('handbooks.title')"
    :subtitle="__('handbooks.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('handbooks.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:filters>
        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('handbooks.target_audience') }}</label>
        <select wire:model.live="filters.target_audience" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="all">{{ __('handbooks.audience_all') }}</option>
            <option value="student">{{ __('handbooks.audience_student') }}</option>
            <option value="teacher">{{ __('handbooks.audience_teacher') }}</option>
            <option value="supervisor">{{ __('handbooks.audience_supervisor') }}</option>
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('handbooks.status') }}</label>
        <select wire:model.live="filters.is_active" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="1">{{ __('handbooks.active') }}</option>
            <option value="0">{{ __('handbooks.inactive') }}</option>
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
                <x-mary-badge :value="$handbook->is_active ? __('handbooks.active') : __('handbooks.inactive')"
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
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('handbooks.edit') : __('handbooks.create')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="store" class="space-y-4">
                <div class="space-y-4">
                    <x-mary-input :label="__('handbooks.title_field')" wire:model="form.title" />
                    <x-mary-textarea :label="__('handbooks.content_field')" wire:model="form.content" rows="8" />
                    <x-mary-input :label="__('handbooks.version_field')" type="number" wire:model="form.version" />
                    <x-mary-select :label="__('handbooks.target_audience')" wire:model="form.target_audience"
                        :options="[
                            ['id' => 'all', 'name' => __('handbooks.audience_all')],
                            ['id' => 'student', 'name' => __('handbooks.audience_student')],
                            ['id' => 'teacher', 'name' => __('handbooks.audience_teacher')],
                            ['id' => 'supervisor', 'name' => __('handbooks.audience_supervisor')],
                        ]" />
                    <x-mary-file wire:model="file" :label="__('handbooks.file')" accept="application/pdf" />
                    @if($form->id)
                        <x-mary-toggle :label="__('handbooks.remove_file')" wire:model="removeFile" />
                    @endif
                    <x-mary-toggle :label="__('handbooks.active')" wire:model="form.is_active" />
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost" />
                    <x-mary-button :label="__('common.actions.save')" wire:click="store" class="btn-primary" spinner="store" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
