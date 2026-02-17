<div>
    <x-ui::main title="{{ __('internship::ui.requirement_title') }}" subtitle="{{ __('internship::ui.requirement_subtitle') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('internship::ui.add_requirement') }}" icon="tabler.plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>

        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('internship::ui.search_requirement') }}" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <x-ui::table :headers="[
                ['key' => 'name', 'label' => __('internship::ui.requirement_name')],
                ['key' => 'type', 'label' => __('internship::ui.requirement_type')],
                ['key' => 'is_mandatory', 'label' => __('internship::ui.mandatory')],
                ['key' => 'is_active', 'label' => __('internship::ui.active')],
                ['key' => 'academic_year', 'label' => __('internship::ui.academic_year')],
                ['key' => 'actions', 'label' => ''],
            ]" :rows="$this->records" with-pagination>
                @scope('cell_name', $requirement)
                    <div class="font-semibold">{{ $requirement->name }}</div>
                    @if($requirement->description)
                        <div class="text-xs opacity-70">{{ $requirement->description }}</div>
                    @endif
                @endscope

                @scope('cell_type', $requirement)
                    <x-ui::badge :label="__('internship::ui.' . $requirement->type)" class="badge-outline" />
                @endscope

                @scope('cell_is_mandatory', $requirement)
                    <x-ui::icon :name="$requirement->is_mandatory ? 'tabler.check' : 'tabler.x'" class="{{ $requirement->is_mandatory ? 'text-success' : 'text-error' }}" />
                @endscope

                @scope('cell_is_active', $requirement)
                    <x-ui::badge :label="$requirement->is_active ? __('internship::ui.active') : __('shared::ui.inactive')" 
                        class="{{ $requirement->is_active ? 'badge-success' : 'badge-ghost' }} badge-sm" />
                @endscope

                @scope('cell_actions', $requirement)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $requirement->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $requirement->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal id="requirement-form-modal" wire:model="formModal" title="{{ $form->id ? __('internship::ui.edit_requirement') : __('internship::ui.add_requirement') }}">
        <x-ui::form wire:submit="save">
            <x-ui::input label="{{ __('internship::ui.requirement_name') }}" wire:model="form.name" required />
            <x-ui::textarea label="{{ __('shared::ui.description') }}" wire:model="form.description" />
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::select 
                    label="{{ __('internship::ui.requirement_type') }}" 
                    wire:model="form.type" 
                    :options="[
                        ['id' => 'document', 'name' => __('internship::ui.document')],
                        ['id' => 'skill', 'name' => __('internship::ui.skill')],
                        ['id' => 'condition', 'name' => __('internship::ui.condition')],
                    ]" 
                    required 
                />
                <x-ui::input label="{{ __('internship::ui.academic_year') }}" wire:model="form.academic_year" placeholder="YYYY/YYYY" required />
            </div>

            <div class="flex gap-4">
                <x-ui::checkbox label="{{ __('internship::ui.mandatory') }}" wire:model="form.is_mandatory" />
                <x-ui::checkbox label="{{ __('internship::ui.active') }}" wire:model="form.is_active" />
            </div>

            <x-slot:actions>
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('shared::ui.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal id="requirement-confirm-modal" wire:model="confirmModal" title="{{ __('shared::ui.confirmation') }}">
        <p>{{ __('internship::ui.delete_requirement_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('shared::ui.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>
