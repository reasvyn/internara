<div>
    <x-ui::header 
        wire:key="company-manager-header"
        title="{{ __('internship::ui.company_title') }}" 
        subtitle="{{ __('internship::ui.company_subtitle') }}"
    >
        <x-slot:actions wire:key="company-manager-actions">
            <x-ui::button label="{{ __('internship::ui.add_company') }}" icon="tabler.plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('internship::ui.search_company') }}" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
                <x-mary-table 
                    class="table-zebra table-md w-full"
                    :headers="[
                        ['key' => 'name', 'label' => __('internship::ui.company_name')],
                        ['key' => 'business_field', 'label' => __('internship::ui.business_field')],
                        ['key' => 'phone', 'label' => __('internship::ui.company_phone')],
                        ['key' => 'email', 'label' => __('internship::ui.company_email')],
                        ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
                    ]" 
                    :rows="$this->records" 
                    with-pagination
                >
                    @scope('cell_actions', $company)
                        <div class="flex items-center justify-end gap-1">
                            <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-xs" wire:click="edit('{{ $company->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                            <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-xs" wire:click="discard('{{ $company->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal id="company-form-modal" wire:model="formModal" title="{{ $form->id ? __('internship::ui.edit_company') : __('internship::ui.add_company') }}">
        <x-ui::form wire:submit="save">
            <x-ui::input label="{{ __('internship::ui.company_name') }}" wire:model="form.name" required />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input label="{{ __('internship::ui.business_field') }}" wire:model="form.business_field" />
                <x-ui::input label="{{ __('internship::ui.leader_name') }}" wire:model="form.leader_name" />
            </div>

            <x-ui::textarea label="{{ __('internship::ui.company_address') }}" wire:model="form.address" rows="2" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input label="{{ __('internship::ui.company_phone') }}" wire:model="form.phone" />
                <x-ui::input label="{{ __('internship::ui.company_fax') }}" wire:model="form.fax" />
            </div>
            
            <x-ui::input label="{{ __('internship::ui.company_email') }}" type="email" wire:model="form.email" />

            <x-slot:actions>
                <x-ui::button label="{{ __('ui::common.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('ui::common.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal id="company-confirm-modal" wire:model="confirmModal" title="{{ __('ui::common.confirm') }}">
        <p>{{ __('internship::ui.delete_company_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('ui::common.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('ui::common.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>
