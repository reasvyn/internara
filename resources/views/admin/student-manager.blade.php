<x-shared::ui.record-manager
    :title="__('user.student.title')"
    :subtitle="__('user.student.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('user.student.new')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" wire:click="export" />
    </x-slot:extraMenu>

    <x-slot:filters>
        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.student.department') }}</label>
        <select wire:model.live="filters.department_id" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            @foreach($this->departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.manager.created_from') }}</label>
        <input wire:model.live="filters.created_from" type="date" class="input input-bordered input-sm w-full text-sm" />

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.manager.created_to') }}</label>
        <input wire:model.live="filters.created_to" type="date" class="input input-bordered input-sm w-full text-sm" />
    </x-slot:filters>

    <x-shared::ui.selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item :title="__('common.actions.delete_selected')" icon="o-trash" class="text-error"
                    wire:confirm="{{ __('common.actions.confirm_action') }}" wire:click="deleteSelected" />
                <x-mary-menu-item :title="__('user.student.archive_filtered')" icon="o-archive-box" class="text-warning"
                    wire:click="archiveAllFiltered" />
            </div>
        </x-mary-dropdown>
    </x-shared::ui.selection-bar>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm"
        >
            @scope('cell_name', $user)
                <div class="flex items-center gap-3 py-1">
                    <x-shared::ui.avatar :user="$user" size="size-9" />
                    <div class="flex flex-col">
                        <span class="font-medium text-sm">{{ $user->name }}</span>
                        <span class="text-xs text-base-content/50">{{ $user->email }}</span>
                    </div>
                </div>
            @endscope

            @scope('actions', $user)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $user->id }}')" :aria-label="__('common.actions.edit')" />
                    <x-mary-button icon="o-key" class="btn-ghost btn-sm text-primary" wire:click="showSlip('{{ $user->id }}')" :aria-label="__('user.manager.account_slip')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:confirm="{{ __('common.actions.confirm_action') }}" wire:click="delete('{{ $user->id }}')" :aria-label="__('common.actions.delete')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="userModal" :title="$form->id ? __('user.student.edit') : __('user.student.new')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('user.manager.account') }}</p>
                    <x-mary-input :label="__('user.fields.full_name')" wire:model="form.name" icon="o-user" />
                    <x-mary-input :label="__('user.fields.email')" type="email" wire:model="form.email" icon="o-envelope" />
                </div>

                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('user.student.academic_info') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input :label="__('user.student.nisn')" wire:model="form.national_id_number" icon="o-identification" />
                        <x-mary-input :label="__('user.student.nis')" wire:model="form.student_id_number" icon="o-document-text" />
                    </div>
                    <x-mary-select :label="__('user.student.department')" wire:model="form.department_id" :options="$this->departments" icon="o-rectangle-group" class="mt-4" />
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('userModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('user.student.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
    @include('admin.components.account-slip-modal')
</x-shared::ui.record-manager>
