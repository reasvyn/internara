<x-core::ui.record-manager
    :title="__('user.admin.title')"
    :subtitle="__('user.admin.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('user.admin.new')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" wire:click="export" />
    </x-slot:extraMenu>

    <x-slot:selectionBar>
        <x-mary-button
            :label="__('common.actions.delete_selected')"
            icon="o-trash"
            class="btn-sm btn-error"
            wire:click="askDeleteSelected"
        />
    </x-slot:selectionBar>

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
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-sm">{{ $user->name }}</span>
                        @if($user->hasRole('super_admin'))
                            <x-mary-icon name="o-shield-check" class="size-4 text-primary" :tooltip="__('user.manager.protected')" />
                        @endif
                    </div>
                    <span class="text-xs text-base-content/50">{{ $user->email }}</span>
                </div>
            </div>
        @endscope

        @scope('actions', $user)
            @if($user->hasRole('super_admin'))
                <div class="flex justify-end">
                    <span class="text-xs text-base-content/40 italic">{{ __('user.admin.protected') }}</span>
                </div>
            @else
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $user->id }}')" :aria-label="__('common.actions.edit')" />
                    @if($user->id !== auth()->id())
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="askDelete('{{ $user->id }}')" :aria-label="__('common.actions.delete')" />
                    @endif
                </div>
            @endif
        @endscope
    </x-mary-table>

    <x-slot:modal>
        <x-mary-modal wire:model="userModal" :title="$form->id ? __('user.admin.edit') : __('user.admin.new')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('user.admin.account') }}</p>
                    <x-mary-input :label="__('user.fields.full_name')" wire:model="form.name" icon="o-user" />
                    <x-mary-input :label="__('user.fields.email')" type="email" wire:model="form.email" icon="o-envelope" />

                    @if(!$form->id)
                        <div class="bg-info/10 border border-info/20 text-info-content p-4 rounded-xl flex items-start gap-3 mt-4">
                            <x-mary-icon name="o-information-circle" class="size-5 text-info shrink-0 mt-0.5" />
                            <div>
                                <span class="text-xs font-semibold text-info block mb-0.5">{{ __('common.notice') }}</span>
                                <span class="text-xs leading-relaxed">{{ __('setup.wizard.username_notice') }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('userModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('user.admin.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>

    <x-core::ui.confirm :message="__('common.actions.confirm_action')" />
</x-core::ui.record-manager>
