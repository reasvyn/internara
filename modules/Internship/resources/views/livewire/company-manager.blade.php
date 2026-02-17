<div>
    <x-ui::main title="{{ __('internship::ui.company_title') }}" subtitle="{{ __('internship::ui.company_subtitle') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('internship::ui.add_company') }}" icon="tabler.plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>

        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('internship::ui.search_company') }}" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <x-ui::table :headers="[
                ['key' => 'name', 'label' => __('internship::ui.company_name')],
                ['key' => 'business_field', 'label' => __('internship::ui.business_field')],
                ['key' => 'phone', 'label' => __('internship::ui.company_phone')],
                ['key' => 'email', 'label' => __('internship::ui.company_email')],
                ['key' => 'actions', 'label' => ''],
            ]" :rows="$this->records" with-pagination>
                @scope('cell_actions', $company)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $company->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $company->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
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
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('shared::ui.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal id="company-confirm-modal" wire:model="confirmModal" title="{{ __('shared::ui.confirmation') }}">
        <p>{{ __('internship::ui.delete_company_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('shared::ui.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>
