<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('user::ui.manager.table.total_users')" 
            :value="$this->stats['total']" 
            icon="tabler.users" 
            variant="metadata" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('permission::roles.student')" 
            :value="$this->stats['students']" 
            icon="tabler.user-check" 
            variant="primary" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('user::ui.manager.table.staff')" 
            :value="$this->stats['staff']" 
            icon="tabler.user-star" 
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

            @scope('cell_role_labels', $user, $this)
                <div class="flex flex-wrap gap-1.5">
                    @foreach($user->role_labels as $role)
                        <x-ui::badge 
                            :value="__('permission::roles.' . $role)" 
                            :variant="$this->roleBadgeVariant($role)"
                            class="badge-sm font-black text-[9px] uppercase tracking-tighter rounded-lg"
                        />
                    @endforeach
                </div>
            @endscope

            @scope('cell_display_status', $user, $this)
                <x-ui::badge 
                    :value="__('user::ui.manager.form.' . $user->display_status)" 
                    :variant="$this->statusBadgeVariant($user->display_status)"
                    class="badge-sm font-black text-[9px] uppercase tracking-widest rounded-lg shadow-sm"
                />
            @endscope

            @scope('cell_actions', $user)
                <div class="flex justify-end gap-2">
                    <x-ui::button
                        icon="tabler.edit"
                        variant="tertiary"
                        wire:click="edit('{{ $user->id }}')"
                        class="btn-xs hover:bg-primary/10 border-none shadow-none text-primary"
                        tooltip="{{ __('ui::common.edit') }}"
                    />
                    @if(auth()->user()?->hasRole('super-admin') && !in_array('super-admin', $user->role_labels))
                        <x-ui::button
                            icon="tabler.trash"
                            variant="tertiary"
                            wire:click="discard('{{ $user->id }}')"
                            wire:confirm="{{ __('ui::common.delete_confirm') }}"
                            class="text-error btn-xs hover:bg-error/10 border-none shadow-none"
                            tooltip="{{ __('ui::common.delete') }}"
                        />
                    @elseif(in_array('super-admin', $user->role_labels))
                        <x-ui::badge :value="__('user::ui.viewer.system_protected')" variant="neutral" class="badge-xs font-black text-[8px] uppercase tracking-widest opacity-30" />
                    @endif
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
                            :label="__('user::ui.manager.filters.role')"
                            icon="tabler.shield-lock"
                            wire:model.live="filters.role"
                            :options="array_filter([
                                ['id' => 'student',  'name' => __('permission::roles.student')],
                                ['id' => 'teacher',  'name' => __('permission::roles.teacher')],
                                ['id' => 'mentor',   'name' => __('permission::roles.mentor')],
                                auth()->user()?->hasRole('super-admin') ? ['id' => 'admin',       'name' => __('permission::roles.admin')]       : null,
                                auth()->user()?->hasRole('super-admin') ? ['id' => 'super-admin', 'name' => __('permission::roles.super-admin')] : null,
                                ['id' => 'no_role', 'name' => __('user::ui.viewer.no_role')],
                            ])"
                            :placeholder="__('user::ui.manager.filters.all_roles')"
                        />

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
    </x-ui::record-manager>
</div>
