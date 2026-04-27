<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('user::ui.manager.table.total_users')" 
            :value="$this->stats['total']" 
            icon="tabler.shield-check" 
            variant="metadata" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('permission::roles.admin')" 
            :value="$this->stats['admins']" 
            icon="tabler.user-shield" 
            variant="info" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('user::ui.manager.form.active')" 
            :value="$this->stats['active']" 
            icon="tabler.circle-check" 
            variant="success" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('admin::ui.manager.invitation_statuses.pending')" 
            :value="$this->stats['pending']" 
            icon="tabler.mail-forward" 
            variant="warning" 
            class="stat-enterprise" 
        />
    </div>

    <x-ui::record-manager>
        {{-- 1. Table Customization via Scopes --}}
        <x-slot:tableCells>
            @scope('cell_name', $user)
                <div class="flex items-center gap-3 group">
                    <x-ui::avatar :src="$user->avatar_url" :title="$user->name" class="rounded-xl size-10 shadow-sm transition-transform group-hover:scale-110" />
                    <div class="flex flex-col">
                        <span class="font-bold text-sm text-base-content/90 tracking-tight group-hover:text-primary transition-colors">{{ $user->name }}</span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            @if($user->is_super_admin)
                                <x-ui::badge value="SUPER" variant="error" class="badge-xs font-black text-[7px] tracking-widest px-1" />
                            @else
                                <x-ui::badge value="ADMIN" variant="neutral" class="badge-xs font-black text-[7px] tracking-widest px-1 opacity-40" />
                            @endif
                        </div>
                    </div>
                </div>
            @endscope

            @scope('cell_invitation_status', $user, $manager)
                <x-ui::badge 
                    :value="__('admin::ui.manager.invitation_statuses.' . $user->invitation_status)" 
                    :variant="$manager->statusBadgeVariant($user->invitation_status)"
                    class="badge-sm font-black text-[9px] uppercase tracking-widest rounded-lg shadow-sm"
                />
            @endscope

            @scope('actions', $user)
                <div class="flex justify-end gap-2">
                    @if($user->invitation_status !== 'accepted')
                        <x-ui::button
                            icon="tabler.mail-forward"
                            variant="tertiary"
                            wire:click="reinvite('{{ $user->id }}')"
                            class="btn-xs hover:bg-warning/10 border-none shadow-none text-warning"
                            tooltip="{{ __('admin::ui.manager.reinvite_action') }}"
                        />
                    @endif
                    <x-ui::button
                        icon="tabler.edit"
                        variant="tertiary"
                        wire:click="edit('{{ $user->id }}')"
                        class="btn-xs hover:bg-primary/10 border-none shadow-none text-primary"
                        tooltip="{{ __('ui::common.edit') }}"
                    />
                    @if(!$user->is_super_admin)
                        <x-ui::button
                            icon="tabler.trash"
                            variant="tertiary"
                            wire:click="discard('{{ $user->id }}')"
                            wire:confirm="{{ __('ui::common.delete_confirm') }}"
                            class="text-error btn-xs hover:bg-error/10 border-none shadow-none"
                            tooltip="{{ __('ui::common.delete') }}"
                        />
                    @else
                        <div class="size-6 flex items-center justify-center opacity-20">
                            <x-ui::icon name="tabler.lock" class="size-3.5" />
                        </div>
                    @endif
                </div>
            @endscope
        </x-slot:tableCells>

        {{-- 2. Form Layout --}}
        <x-slot:formFields>
            <div class="space-y-8 p-6">
                <div class="p-6 bg-info/5 text-info rounded-[2rem] border border-info/10 flex items-start gap-5 shadow-sm">
                    <div class="size-10 rounded-2xl bg-info/20 flex items-center justify-center shrink-0">
                        <x-ui::icon name="tabler.info-circle" class="size-6" />
                    </div>
                    <p class="text-[13px] font-semibold leading-relaxed">{{ __('admin::ui.manager.invitation_notice') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <x-ui::input :label="__('user::ui.manager.form.full_name')" icon="tabler.user-edit" wire:model="form.name" required />
                    <x-ui::input :label="__('user::ui.manager.form.email')" icon="tabler.mail" wire:model="form.email" required />
                </div>
            </div>
        </x-slot:formFields>
    </x-ui::record-manager>
</div>
