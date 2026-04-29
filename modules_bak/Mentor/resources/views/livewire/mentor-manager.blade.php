<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('user::ui.manager.table.total_users')" 
            :value="$this->stats['total']" 
            icon="tabler.users" 
            variant="metadata" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('user::ui.manager.form.active')" 
            :value="$this->stats['active']" 
            icon="tabler.user-check" 
            variant="success" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('user::ui.manager.form.activation_pending_claim')" 
            :value="$this->stats['pending_claim']" 
            icon="tabler.user-question" 
            variant="warning" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('admin::ui.dashboard.stats.new_registrations')" 
            :value="$this->stats['new_this_week']" 
            icon="tabler.sparkles" 
            variant="primary" 
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
                        <span class="text-[10px] opacity-40 uppercase tracking-widest font-black">{{ $user->username }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_display_status', $user, $manager)
                <x-ui::badge 
                    :value="__('user::ui.manager.form.' . $user->display_status)" 
                    :variant="$manager->statusBadgeVariant($user->display_status)"
                    class="badge-sm font-black text-[9px] uppercase tracking-widest rounded-lg shadow-sm"
                />
            @endscope

            @scope('cell_activation_status', $user, $manager)
                <x-ui::badge 
                    :value="__('user::ui.manager.form.activation_' . $user->activation_status)" 
                    :variant="$manager->activationStatusBadgeVariant($user->activation_status)"
                    class="badge-sm font-black text-[9px] uppercase tracking-widest rounded-lg shadow-sm"
                />
            @endscope

            @scope('cell_actions', $user)
                <div class="flex justify-end gap-2">
                    <x-ui::button
                        icon="tabler.key"
                        variant="tertiary"
                        wire:click="reissueActivationCode('{{ $user->id }}')"
                        class="btn-xs hover:bg-warning/10 border-none shadow-none text-warning"
                        tooltip="{{ __('user::ui.manager.form.reissue_code') }}"
                    />
                    <x-ui::button
                        icon="tabler.edit"
                        variant="tertiary"
                        wire:click="edit('{{ $user->id }}')"
                        class="btn-xs hover:bg-primary/10 border-none shadow-none text-primary"
                        tooltip="{{ __('ui::common.edit') }}"
                    />
                    <x-ui::button
                        icon="tabler.trash"
                        variant="tertiary"
                        wire:click="discard('{{ $user->id }}')"
                        wire:confirm="{{ __('ui::common.delete_confirm') }}"
                        class="text-error btn-xs hover:bg-error/10 border-none shadow-none"
                        tooltip="{{ __('ui::common.delete') }}"
                    />
                </div>
            @endscope
        </x-slot:tableCells>

        {{-- 2. Modernized Filters --}}
        <x-slot:filters>
            <x-ui::dropdown :close-on-content-click="false" right>
                <x-slot:trigger>
                    <x-ui::button icon="tabler.filter" variant="secondary" class="gap-2 font-bold text-xs uppercase tracking-widest px-6">
                        <span>{{ __('user::ui.manager.filters.open') }}</span>
                        @if($this->activeFilterCount() > 0)
                            <x-ui::badge :value="$this->activeFilterCount()" variant="info" class="badge-xs" />
                        @endif
                    </x-ui::button>
                </x-slot:trigger>

                <div class="w-[min(92vw,35rem)] space-y-8 p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <x-ui::select
                            :label="__('user::ui.manager.filters.status')"
                            icon="tabler.circle-check"
                            wire:model.live="filters.status"
                            :options="[
                                ['id' => 'active',   'name' => __('user::ui.manager.form.active')],
                                ['id' => 'pending',  'name' => __('user::ui.manager.form.pending')],
                                ['id' => 'inactive', 'name' => __('user::ui.manager.form.inactive')],
                            ]"
                            :placeholder="__('user::ui.manager.filters.all_statuses')"
                        />

                        <x-ui::input
                            :label="__('user::ui.manager.filters.created_from')"
                            icon="tabler.calendar-down"
                            type="date"
                            wire:model.live="filters.created_from"
                        />

                        <x-ui::input
                            :label="__('user::ui.manager.filters.created_to')"
                            icon="tabler.calendar-up"
                            type="date"
                            wire:model.live="filters.created_to"
                        />
                    </div>

                    <div class="flex justify-between items-center pt-6 border-t border-base-content/5">
                        <span class="text-[10px] font-black uppercase tracking-widest opacity-30">{{ __('user::ui.manager.filters.active_count', ['count' => $this->activeFilterCount()]) }}</span>
                        <x-ui::button
                            :label="__('user::ui.manager.filters.reset')"
                            icon="tabler.filter-off"
                            variant="ghost"
                            class="text-[10px] font-black uppercase tracking-[0.2em]"
                            wire:click="resetFilters"
                        />
                    </div>
                </div>
            </x-ui::dropdown>
        </x-slot:filters>

        {{-- 3. Form Layout --}}
        <x-slot:formFields>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-4">
                <div class="space-y-6">
                    <x-ui::input :label="__('user::ui.manager.form.full_name')" icon="tabler.user-edit" wire:model="form.name" required />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input :label="__('user::ui.manager.form.email')" icon="tabler.mail" wire:model="form.email" required />
                        <x-ui::input :label="__('user::ui.manager.form.username')" icon="tabler.at" wire:model="form.username" />
                    </div>
                </div>

                <div class="space-y-6">
                    <x-ui::select :label="__('user::ui.manager.form.status')" icon="tabler.circle-check" wire:model="form.status" :options="[['id' => 'active', 'name' => __('user::ui.manager.form.active')], ['id' => 'pending', 'name' => __('user::ui.manager.form.pending')], ['id' => 'inactive', 'name' => __('user::ui.manager.form.inactive')]]" required />
                    <x-ui::textarea :label="__('user::ui.manager.form.address')" icon="tabler.map-pin" wire:model="form.profile.address" />
                </div>
            </div>
        </x-slot:formFields>
    </x-ui::record-manager>

    {{-- Credential Slips Modal --}}
    <x-ui::modal wire:model="credentialSlipsModal" :title="__('user::ui.manager.credential_slips.title')" class="backdrop-blur-xl">
        <div class="space-y-6 py-4">
            <div class="p-6 bg-warning/10 text-warning rounded-3xl border border-warning/10 flex items-start gap-4">
                <x-ui::icon name="tabler.alert-triangle" class="size-6 shrink-0" />
                <p class="text-xs font-bold leading-relaxed">{{ __('user::ui.manager.credential_slips.warning') }}</p>
            </div>

            <div class="space-y-4 max-h-[50vh] overflow-y-auto px-1 custom-scrollbar">
                @foreach($credentialSlips as $slip)
                    <div class="p-6 bg-base-200/50 rounded-[2rem] border border-base-content/5 flex flex-col gap-4 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:scale-125 transition-transform duration-700">
                            <x-ui::icon name="tabler.id-badge-2" class="size-24" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-lg font-black tracking-tight">{{ $slip['name'] }}</span>
                            <span class="text-[10px] font-black uppercase tracking-widest opacity-40">{{ $slip['username'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4 pt-4 border-t border-base-content/5">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black uppercase tracking-widest opacity-30">{{ __('user::ui.manager.credential_slips.code') }}</span>
                                <span class="text-xl font-mono font-black tracking-[0.2em] text-primary">{{ $slip['code'] }}</span>
                            </div>
                            <x-ui::button icon="tabler.copy" variant="secondary" class="btn-circle btn-sm shadow-inner" x-on:click="navigator.clipboard.writeText('{{ $slip['code'] }}'); $el.classList.add('btn-success'); setTimeout(() => $el.classList.remove('btn-success'), 2000)" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" class="flex-1 font-bold py-4 rounded-2xl" wire:click="closeCredentialSlips" />
        </x-slot:actions>
    </x-ui::modal>
</div>
