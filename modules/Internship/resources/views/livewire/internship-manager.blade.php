<div>
    <x-ui::main title="{{ __('internship::ui.program_title') }}" subtitle="{{ __('internship::ui.program_subtitle') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('internship::ui.add_program') }}" icon="o-plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>

        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('internship::ui.search_program') }}" icon="o-magnifying-glass" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <x-mary-table :headers="[
                ['key' => 'title', 'label' => __('internship::ui.title')],
                ['key' => 'year', 'label' => __('internship::ui.year')],
                ['key' => 'semester', 'label' => __('internship::ui.semester')],
                ['key' => 'date_start', 'label' => __('internship::ui.date_start')],
                ['key' => 'date_finish', 'label' => __('internship::ui.date_finish')],
            ]" :rows="$this->records" with-pagination>
                @scope('cell_date_start', $program)
                    {{ $program->date_start->format('d/m/Y') }}
                @endscope
                @scope('cell_date_finish', $program)
                    {{ $program->date_finish->format('d/m/Y') }}
                @endscope

                @scope('actions', $program)
                    <div class="flex gap-2">
                        <x-ui::button icon="o-pencil" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $program->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $program->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-mary-table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="formModal" title="{{ $form->id ? __('internship::ui.edit_program') : __('internship::ui.add_program') }}">
        <x-ui::form wire:submit="save">
            <x-ui::input label="{{ __('internship::ui.title') }}" wire:model="form.title" required />
            <x-ui::textarea label="{{ __('shared::ui.description') }}" wire:model="form.description" />
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input label="{{ __('internship::ui.year') }}" type="number" wire:model="form.year" required />
                <x-ui::select 
                    label="{{ __('internship::ui.semester') }}" 
                    wire:model="form.semester" 
                    :options="[['id' => 'Ganjil', 'name' => 'Ganjil'], ['id' => 'Genap', 'name' => 'Genap']]" 
                    required 
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input label="{{ __('internship::ui.date_start') }}" type="date" wire:model="form.date_start" required />
                <x-ui::input label="{{ __('internship::ui.date_finish') }}" type="date" wire:model="form.date_finish" required />
            </div>

            <x-slot:actions>
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('shared::ui.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-mary-modal>

    {{-- Confirm Delete Modal --}}
    <x-mary-modal wire:model="confirmModal" title="{{ __('shared::ui.confirmation') }}">
        <p>{{ __('internship::ui.delete_program_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('shared::ui.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-mary-modal>
</div>