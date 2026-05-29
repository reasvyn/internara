<x-shared::ui.record-manager
    :title="__('user.mentor.title')"
    :subtitle="__('user.mentor.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('user.mentor.new')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:filters>
        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.mentor.type') }}</label>
        <select wire:model.live="filters.type" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="school_teacher">{{ __('user.mentor.school_teacher') }}</option>
            <option value="industry_supervisor">{{ __('user.mentor.industry_supervisor') }}</option>
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.mentor.active') }}</label>
        <select wire:model.live="filters.is_active" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="yes">{{ __('common.yes') }}</option>
            <option value="no">{{ __('common.no') }}</option>
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.manager.created_from') }}</label>
        <input wire:model.live="filters.created_from" type="date" class="input input-bordered input-sm w-full text-sm" />

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.manager.created_to') }}</label>
        <input wire:model.live="filters.created_to" type="date" class="input input-bordered input-sm w-full text-sm" />
    </x-slot:filters>

    <x-shared::ui.selection-bar>
        <x-mary-button
            :label="__('common.actions.delete_selected')"
            icon="o-trash"
            class="btn-sm btn-error text-white"
            :wire:confirm="__('common.actions.confirm_action')"
            wire:click="deleteSelected"
        />
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
            @scope('cell_name', $mentor)
                <div class="flex items-center gap-3 py-1">
                    <x-shared::ui.avatar :user="$mentor->user" size="size-9" />
                    <div class="flex flex-col">
                        <span class="font-medium text-sm">{{ $mentor->user->name }}</span>
                        <span class="text-xs text-base-content/50">{{ $mentor->user->email }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_type', $mentor)
                @if($mentor->type === \App\Domain\Mentor\Models\Mentor::TYPE_SCHOOL_TEACHER)
                    <x-mary-badge :value="__('user.mentor.school_teacher')" class="badge-primary badge-sm font-medium text-[10px]" />
                @else
                    <x-mary-badge :value="__('user.mentor.industry_supervisor')" class="badge-secondary badge-sm font-medium text-[10px]" />
                @endif
            @endscope

            @scope('cell_is_active', $mentor)
                @if($mentor->is_active)
                    <x-mary-icon name="o-check-circle" class="size-5 text-success" />
                @else
                    <x-mary-icon name="o-x-circle" class="size-5 text-error" />
                @endif
            @endscope

            @scope('actions', $mentor)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $mentor->id }}')" :aria-label="__('common.actions.edit')" />
                    <x-mary-button icon="o-key" class="btn-ghost btn-sm text-primary" wire:click="showSlip('{{ $mentor->id }}')" :aria-label="__('user.manager.account_slip')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:confirm="{{ __('common.actions.confirm_action') }}" wire:click="delete('{{ $mentor->id }}')" :aria-label="__('common.actions.delete')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="userModal" :title="$form->id ? __('user.mentor.edit') : __('user.mentor.new')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('user.manager.account') }}</p>
                    <x-mary-input :label="__('user.fields.full_name')" wire:model="form.name" icon="o-user" />
                    <x-mary-input :label="__('user.fields.email')" type="email" wire:model="form.email" icon="o-envelope" />
                    <x-mary-select :label="__('user.mentor.type')" wire:model="form.type" :options="[
                        ['id' => \App\Domain\Mentor\Models\Mentor::TYPE_SCHOOL_TEACHER, 'name' => __('user.mentor.school_teacher')],
                        ['id' => \App\Domain\Mentor\Models\Mentor::TYPE_INDUSTRY_SUPERVISOR, 'name' => __('user.mentor.industry_supervisor')],
                    ]" icon="o-user-circle" class="mt-4" />
                    <x-mary-toggle :label="__('user.mentor.active')" wire:model="form.is_active" class="mt-4" />
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('userModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('user.mentor.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
    @include('admin.components.account-slip-modal')
</x-shared::ui.record-manager>
