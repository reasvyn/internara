<div>
    <x-ui::header :title="__('department::ui.title')" :subtitle="__('department::ui.subtitle')">
        <x-slot:actions>
            <x-ui::button :label="__('department::ui.add')" icon="tabler.plus" variant="primary" wire:click="add" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input :placeholder="__('department::ui.search_placeholder')" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <div class="w-full overflow-x-auto rounded-xl border border-base-200 bg-base-100 shadow-sm">
                <x-mary-table 
                    class="table-zebra table-md w-full"
                    :headers="[
                        ['key' => 'name', 'label' => __('department::ui.name')],
                        ['key' => 'description', 'label' => __('ui::common.description')],
                        ['key' => 'created_at', 'label' => __('ui::common.created_at')],
                        ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
                    ]" 
                    :rows="$this->records" 
                    with-pagination
                >
                    @scope('cell_created_at', $department)
                        <span class="text-xs opacity-70">{{ $department->created_at->translatedFormat('d M Y H:i') }}</span>
                    @endscope

                    @scope('cell_actions', $department)
                        <div class="flex items-center justify-end gap-1">
                            <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-xs" wire:click="edit('{{ $department->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                            <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-xs" wire:click="discard('{{ $department->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal id="department-form-modal" wire:model="formModal" :title="$form->id ? __('department::ui.edit') : __('department::ui.add')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('department::ui.name')" wire:model="form.name" required />
            <x-ui::textarea :label="__('ui::common.description')" wire:model="form.description" />

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal id="department-confirm-modal" wire:model="confirmModal" :title="__('ui::common.confirm')">
        <p>{{ __('department::ui.delete_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>