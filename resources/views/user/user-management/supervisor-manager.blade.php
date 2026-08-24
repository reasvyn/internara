<x-core::ui.record-manager :title="__('user.supervisor.title')" :subtitle="__('user.supervisor.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('user.supervisor.new')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" wire:click="export" />
    </x-slot:extraMenu>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('user.manager.created_from') }}</label>
        <input
            wire:model.live="filters.created_from"
            type="date"
            class="input input-bordered input-sm w-full text-sm"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('user.manager.created_to') }}</label>
        <input wire:model.live="filters.created_to" type="date" class="input input-bordered input-sm w-full text-sm" />
    </x-slot:filters>

    <x-core::ui.selection-bar>
        <x-ts-button
            :text="__('common.actions.delete_selected')"
            icon="trash"
            class="text-white"
            color="red"
            sm
            wire:click="askDeleteSelected"
        />
    </x-core::ui.selection-bar>

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
                    <x-core::ui.avatar :user="$user" size="size-9" />
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{{ $user->name }}</span>
                        <span class="text-base-content/50 text-xs">{{ $user->username }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_profile_company_id', $user)
                <span class="text-sm">{{ $user->profile?->company?->name ?? '—' }}</span>
            @endscope

            @scope('actions', $user)
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
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-ts-modal
            wire="userModal"
            :title="$form->id ? __('user.supervisor.edit') : __('user.supervisor.new')"
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
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-input :label="__('user.supervisor.phone')" wire:model="form.phone" icon="phone" />
                        <x-ts-select.native
                            :label="__('user.supervisor.company')"
                            wire:model="form.company_id"
                            :options="[null => __('user.supervisor.company_placeholder')] + ($this->companies)"
                            icon="building-office"
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('userModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('user.supervisor.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('user.user-management.components.account-slip-modal')

    @include('user.user-management.components.supervisor-guide')
</x-core::ui.record-manager>
