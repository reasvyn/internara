<div>
    <x-ui::main title="{{ __('internship::ui.registration_title') }}" subtitle="{{ __('internship::ui.registration_subtitle') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('internship::ui.add_registration') }}" icon="tabler.plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>

        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('internship::ui.search_registration') }}" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <x-ui::table :headers="[
                ['key' => 'student.name', 'label' => __('internship::ui.student')],
                ['key' => 'internship.title', 'label' => __('internship::ui.program')],
                ['key' => 'placement.company_name', 'label' => __('internship::ui.placement')],
                ['key' => 'teacher.name', 'label' => __('internship::ui.teacher')],
                ['key' => 'mentor.name', 'label' => __('internship::ui.mentor')],
                ['key' => 'status', 'label' => __('internship::ui.status')],
            ]" :rows="$this->records" with-pagination>
                @scope('cell_status', $registration)
                    <x-ui::badge :label="$registration->getStatusLabel()" :class="'badge-' . $registration->getStatusColor()" />
                @endscope

                @scope('actions', $registration)
                    <div class="flex gap-2">
                        @if($registration->latestStatus()?->name !== 'active')
                            <x-ui::button icon="tabler.check" class="btn-ghost btn-sm text-success" wire:click="approve('{{ $registration->id }}')" tooltip="{{ __('shared::ui.approve') }}" />
                        @endif
                        @if($registration->latestStatus()?->name !== 'inactive')
                            <x-ui::button icon="tabler.x" class="btn-ghost btn-sm text-warning" wire:click="reject('{{ $registration->id }}')" tooltip="{{ __('shared::ui.reject') }}" />
                        @endif
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $registration->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $registration->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" title="{{ $form->id ? __('internship::ui.edit_registration') : __('internship::ui.add_registration') }}">
        <x-ui::form wire:submit="save">
            <x-ui::select 
                label="{{ __('internship::ui.student') }}" 
                wire:model="form.student_id" 
                :options="$this->students" 
                placeholder="{{ __('internship::ui.select_student') }}"
                required 
            />
            <x-ui::select 
                label="{{ __('internship::ui.program') }}" 
                wire:model="form.internship_id" 
                :options="$this->internships" 
                placeholder="{{ __('internship::ui.select_program') }}"
                required 
            />
            <x-ui::select 
                label="{{ __('internship::ui.placement') }}" 
                wire:model="form.placement_id" 
                :options="$this->placements" 
                placeholder="{{ __('internship::ui.select_placement') }}"
                required 
            />

            <hr class="my-4 opacity-20" />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::select 
                    label="{{ __('internship::ui.teacher') }}" 
                    wire:model="form.teacher_id" 
                    :options="$this->teachers" 
                    placeholder="{{ __('internship::ui.select_teacher') }}"
                />
                <x-ui::select 
                    label="{{ __('internship::ui.mentor') }}" 
                    wire:model="form.mentor_id" 
                    :options="$this->mentors" 
                    placeholder="{{ __('internship::ui.select_mentor') }}"
                />
            </div>

            <x-slot:actions>
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('shared::ui.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" title="{{ __('shared::ui.confirmation') }}">
        <p>{{ __('internship::ui.delete_registration_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('shared::ui.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>