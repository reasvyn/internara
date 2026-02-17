<div>
    <x-ui::header 
        wire:key="internship-manager-header"
        :title="__('internship::ui.program_title')" 
        :subtitle="__('internship::ui.program_subtitle')"
    >
        <x-slot:actions wire:key="internship-manager-actions">
            <x-ui::button :label="__('internship::ui.add_program')" icon="tabler.plus" variant="primary" wire:click="add" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input :placeholder="__('internship::ui.search_program')" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
                <x-mary-table 
                    class="table-zebra table-md w-full"
                    :headers="[
                        ['key' => 'title', 'label' => __('internship::ui.title')],
                        ['key' => 'year', 'label' => __('internship::ui.year')],
                        ['key' => 'semester', 'label' => __('internship::ui.semester')],
                        ['key' => 'date_start', 'label' => __('internship::ui.date_start')],
                        ['key' => 'date_finish', 'label' => __('internship::ui.date_finish')],
                        ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
                    ]" 
                    :rows="$this->records" 
                    with-pagination
                >
                    @scope('cell_date_start', $program)
                        <span class="text-xs opacity-70">{{ $program->date_start->translatedFormat('d M Y') }}</span>
                    @endscope
                    @scope('cell_date_finish', $program)
                        <span class="text-xs opacity-70">{{ $program->date_finish->translatedFormat('d M Y') }}</span>
                    @endscope

                    @scope('cell_actions', $program)
                        <div class="flex items-center justify-end gap-1">
                            <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-xs" wire:click="edit('{{ $program->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                            <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-xs" wire:click="discard('{{ $program->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal id="internship-form-modal" wire:model="formModal" :title="$form->id ? __('internship::ui.edit_program') : __('internship::ui.add_program')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('internship::ui.title')" wire:model="form.title" required />
            <x-ui::textarea :label="__('ui::common.description')" wire:model="form.description" />
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input :label="__('internship::ui.year')" type="number" wire:model="form.year" required />
                <x-ui::select 
                    :label="__('internship::ui.semester')" 
                    wire:model="form.semester" 
                    :options="[['id' => 'Ganjil', 'name' => __('internship::ui.semester_odd')], ['id' => 'Genap', 'name' => __('internship::ui.semester_even')]]" 
                    required 
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input :label="__('internship::ui.date_start')" type="date" wire:model="form.date_start" required />
                <x-ui::input :label="__('internship::ui.date_finish')" type="date" wire:model="form.date_finish" required />
            </div>

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal id="internship-confirm-modal" wire:model="confirmModal" :title="__('ui::common.confirm')">
        <p>{{ __('internship::ui.delete_program_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>
