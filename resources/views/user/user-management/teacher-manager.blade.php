<x-ui::components.record-manager :title="__('user.teacher.title')" :subtitle="__('user.teacher.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('user.teacher.new')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" wire:click="export" />
    </x-slot:extraMenu>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('user.teacher.created_from') }}</label>
        <input
            wire:model.live="filters.created_from"
            type="date"
            class="input input-bordered input-sm w-full text-sm"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('user.teacher.created_to') }}</label>
        <input wire:model.live="filters.created_to" type="date" class="input input-bordered input-sm w-full text-sm" />
    </x-slot:filters>

    <x-ui::components.selection-bar>
        <x-ts-button
            :text="__('common.actions.delete_selected')"
            icon="trash"
            class="text-white"
            color="red"
            sm
            wire:click="askDeleteSelected"
        />
    </x-ui::components.selection-bar>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm"
        >
            @interact('column_name', $user)
                <div class="flex items-center gap-3 py-1">
                    <x-ui::components.avatar :user="$user" size="size-9" />
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{{ $user->name }}</span>
                        <span class="text-base-content/50 text-xs">{{ $user->username }}</span>
                    </div>
                </div>
            @endinteract

            @interact('column_action', $user)
                <div class="flex justify-end gap-1">
                    <x-ts-button
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $user->id }}')"
                        :aria-label="__('common.actions.edit')"
                    />
                    <x-ts-button
                        icon="key"
                        class="text-primary"
                        color="white"
                        sm
                        wire:click="showSlip('{{ $user->id }}')"
                        :aria-label="__('user.manager.account_slip')"
                    />
                    <x-ts-button
                        icon="trash"
                        class="text-error"
                        color="white"
                        sm
                        wire:click="askDelete('{{ $user->id }}')"
                        :aria-label="__('common.actions.delete')"
                    />
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal
            wire="userModal"
            :title="$form->id ? __('user.teacher.edit') : __('user.teacher.new')"
            separator
            blur
        >
            <form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('user.manager.account') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-input :label="__('user.fields.full_name')" wire:model="form.name" icon="user" />
                        <x-ts-input
                            :label="__('user.fields.email')"
                            type="email"
                            wire:model="form.email"
                            icon="envelope"
                        />
                    </div>
                    <x-ts-input
                        :label="__('user.teacher.id_number')"
                        wire:model="form.id_number"
                        icon="identification"
                        class="mt-4"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('userModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('user.teacher.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('user.user-management.components.account-slip-modal')

    @include('user.user-management.components.teacher-guide')
</x-ui::components.record-manager>
