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

            <x-ui::table :headers="[
                ['key' => 'name', 'label' => __('department::ui.name')],
                ['key' => 'description', 'label' => __('department::ui.description')],
                ['key' => 'created_at', 'label' => __('department::ui.created_at')],
            ]" :rows="$this->records" with-pagination>
                @scope('cell_created_at', $department)
                    <span class="text-xs opacity-70">{{ $department->created_at->translatedFormat('d M Y H:i') }}</span>
                @endscope

                @scope('actions', $department)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info" wire:click="edit('{{ $department->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                        <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error" wire:click="discard('{{ $department->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" :title="$form->id ? __('department::ui.edit') : __('department::ui.add')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('department::ui.name')" wire:model="form.name" required />
            <x-ui::textarea :label="__('department::ui.description')" wire:model="form.description" />

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" :title="__('ui::common.confirm')">
        <p>{{ __('department::ui.delete_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>