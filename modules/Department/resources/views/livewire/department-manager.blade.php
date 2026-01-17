<div>
    <x-ui::main title="{{ __('department::ui.title') }}" subtitle="{{ __('department::ui.subtitle') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('department::ui.add') }}" icon="o-plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>

        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('department::ui.search_placeholder') }}" icon="o-magnifying-glass" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <x-ui::table :headers="[
                ['key' => 'name', 'label' => __('department::ui.name')],
                ['key' => 'description', 'label' => __('department::ui.description')],
                ['key' => 'created_at', 'label' => __('department::ui.created_at')],
            ]" :rows="$this->records" with-pagination>
                @scope('cell_created_at', $department)
                    {{ $department->created_at->format('d/m/Y H:i') }}
                @endscope

                @scope('actions', $department)
                    <div class="flex gap-2">
                        <x-ui::button icon="o-pencil" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $department->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $department->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" title="{{ $form->id ? __('department::ui.edit') : __('department::ui.add') }}">
        <x-ui::form wire:submit="save">
            <x-ui::input label="{{ __('department::ui.name') }}" wire:model="form.name" required />
            <x-ui::textarea label="{{ __('department::ui.description') }}" wire:model="form.description" hint="{{ __('shared::ui.optional') }}" />

            <x-slot:actions>
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('shared::ui.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" title="{{ __('shared::ui.confirmation') }}">
        <p>{{ __('department::ui.delete_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('shared::ui.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>