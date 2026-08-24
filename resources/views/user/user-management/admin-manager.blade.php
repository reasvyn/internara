<x-core::ui.record-manager :title="__('user.admin.title')" :subtitle="__('user.admin.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('user.admin.new')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" wire:click="export" />
    </x-slot:extraMenu>

    <x-slot:selectionBar>
        <x-ts-button
            :text="__('common.actions.delete_selected')"
            icon="trash"
            color="red"
            sm
            wire:click="askDeleteSelected"
        />
    </x-slot:selectionBar>

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
                <x-core::ui.avatar :user="$user" size="size-9" />
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ $user->name }}</span>
                        @if ($user->hasRole('super_admin'))
                            <x-ts-icon
                                name="shield-check"
                                class="text-primary size-4"
                                :tooltip="__('user.manager.protected')"
                            />
                        @endif
                    </div>
                    <span class="text-base-content/50 text-xs">{{ $user->email }}</span>
                </div>
            </div>
        @endinteract

        @interact('column_action', $user)
            @if ($user->hasRole('super_admin'))
                <div class="flex justify-end">
                    <span class="text-base-content/40 text-xs italic">{{ __('user.admin.protected') }}</span>
                </div>
            @else
                <div class="flex justify-end gap-1">
                    <x-ts-button
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $user->id }}')"
                        :aria-label="__('common.actions.edit')"
                    />
                    @if ($user->id !== auth()->id())
                        <x-ts-button
                            icon="trash"
                            class="text-error"
                            color="white"
                            sm
                            wire:click="askDelete('{{ $user->id }}')"
                            :aria-label="__('common.actions.delete')"
                        />
                    @endif
                </div>
            @endif
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal wire="userModal" :title="$form->id ? __('user.admin.edit') : __('user.admin.new')" separator blur>
            <form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('user.admin.account') }}
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

                    @if (! $form->id)
                        <div class="bg-info/10 border-info/20 text-info-content mt-4 flex items-start gap-3 rounded-xl border p-4">
                            <x-ts-icon name="information-circle" class="text-info mt-0.5 size-5 shrink-0" />
                            <div>
                                <span class="text-info mb-0.5 block text-xs font-semibold">{{ __('common.notice') }}</span>
                                <span class="text-xs leading-relaxed">{{ __('setup.wizard.username_notice') }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('userModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('user.admin.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>

    @include('user.user-management.components.admin-guide')
</x-core::ui.record-manager>
