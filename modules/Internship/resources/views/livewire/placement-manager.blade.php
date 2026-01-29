<div>
    <x-ui::main title="{{ __('internship::ui.placement_title') }}" subtitle="{{ __('internship::ui.placement_subtitle') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('internship::ui.add_placement') }}" icon="tabler.plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>

        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('internship::ui.search_placement') }}" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <x-ui::table :headers="[
                ['key' => 'company_name', 'label' => __('internship::ui.company_name')],
                ['key' => 'internship.title', 'label' => __('internship::ui.program')],
                ['key' => 'capacity_quota', 'label' => __('internship::ui.capacity_quota')],
                ['key' => 'contact_person', 'label' => __('internship::ui.contact')],
            ]" :rows="$this->records" with-pagination>
                @scope('actions', $placement)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $placement->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $placement->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" title="{{ $form->id ? __('internship::ui.edit_placement') : __('internship::ui.add_placement') }}">
        <x-ui::form wire:submit="save">
            <x-ui::select 
                label="{{ __('internship::ui.program') }}" 
                wire:model="form.internship_id" 
                :options="$this->internships" 
                placeholder="{{ __('internship::ui.select_program') }}"
                required 
            />
            <x-ui::input label="{{ __('internship::ui.company_name') }}" wire:model="form.company_name" required />
            <x-ui::textarea label="{{ __('internship::ui.company_address') }}" wire:model="form.company_address" />
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input label="{{ __('internship::ui.contact_person') }}" wire:model="form.contact_person" />
                <x-ui::input label="{{ __('internship::ui.contact_number') }}" wire:model="form.contact_number" />
            </div>

            <x-ui::input label="{{ __('internship::ui.capacity_quota') }}" type="number" wire:model="form.capacity_quota" required min="1" />

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <x-ui::select 
                        label="{{ __('internship::ui.mentor') }}" 
                        wire:model="form.mentor_id" 
                        :options="$this->mentors" 
                        placeholder="{{ __('internship::ui.select_mentor') }}"
                    />
                </div>
                <x-ui::button icon="tabler.user-plus" class="btn-outline" wire:click="addMentor" tooltip="{{ __('Add New Mentor') }}" />
            </div>

            <x-slot:actions>
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('shared::ui.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- JIT Mentor Modal --}}
    <x-ui::modal wire:model="mentorModal" title="{{ __('Add New Industry Mentor') }}">
        <x-ui::form wire:submit="saveMentor">
            <x-ui::input label="{{ __('Full Name') }}" wire:model="mentorForm.name" required />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input label="{{ __('Email') }}" type="email" wire:model="mentorForm.email" required />
                <x-ui::input label="{{ __('Username') }}" wire:model="mentorForm.username" required />
            </div>
            <x-ui::input label="{{ __('Password') }}" type="password" wire:model="mentorForm.password" required />

            <x-slot:actions>
                <x-ui::button label="{{ __('Cancel') }}" wire:click="$set('mentorModal', false)" />
                <x-ui::button label="{{ __('Create and Assign') }}" type="submit" class="btn-primary" spinner="saveMentor" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" title="{{ __('shared::ui.confirmation') }}">
        <p>{{ __('internship::ui.delete_placement_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('shared::ui.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>
