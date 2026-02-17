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
                ['key' => 'company.name', 'label' => __('internship::ui.company_name')],
                ['key' => 'internship.title', 'label' => __('internship::ui.program')],
                ['key' => 'quota', 'label' => __('internship::ui.capacity_quota')],
                ['key' => 'company.phone', 'label' => __('internship::ui.contact')],
            ]" :rows="$this->records" with-pagination>
                @scope('cell_quota', $placement)
                    <div class="flex flex-col gap-1 min-w-[120px]">
                        <div class="flex justify-between text-xs">
                            <span>{{ $placement->capacity_quota - $placement->remaining_slots }} / {{ $placement->capacity_quota }}</span>
                            <span class="font-bold">{{ $placement->utilization_percentage }}%</span>
                        </div>
                        <progress class="progress progress-primary w-full" value="{{ $placement->utilization_percentage }}" max="100"></progress>
                    </div>
                @endscope

                @scope('actions', $placement)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $placement->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $placement->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>

        {{-- Form Modal --}}
        <x-ui::modal id="placement-form-modal" wire:model="formModal" title="{{ $form->id ? __('internship::ui.edit_placement') : __('internship::ui.add_placement') }}">
            <x-ui::form wire:submit="save">
                <x-ui::select 
                    label="{{ __('internship::ui.program') }}" 
                    wire:model="form.internship_id" 
                    :options="$this->internships" 
                    placeholder="{{ __('internship::ui.select_program') }}"
                    required 
                />
                
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <x-ui::select 
                            label="{{ __('internship::ui.company_name') }}" 
                            wire:model="form.company_id" 
                            :options="$this->companies" 
                            placeholder="{{ __('internship::ui.select_company') }}"
                            required 
                        />
                    </div>
                    {{-- TODO: Add JIT Company Modal if needed --}}
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
        <x-ui::modal id="placement-mentor-modal" wire:model="mentorModal" title="{{ __('Add New Industry Mentor') }}">
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
        <x-ui::modal id="placement-confirm-modal" wire:model="confirmModal" title="{{ __('shared::ui.confirmation') }}">
            <p>{{ __('internship::ui.delete_placement_confirm') }}</p>
            <x-slot:actions>
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('confirmModal', false)" />
                <x-ui::button label="{{ __('shared::ui.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
            </x-slot:actions>
        </x-ui::modal>
    </x-ui::main>
</div>
