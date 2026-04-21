<x-ui::record-manager>
    <x-slot:filters>
        <x-ui::dropdown :close-on-content-click="false" right>
            <x-slot:trigger>
                <x-ui::button icon="tabler.filter" variant="secondary" class="gap-2">
                    <span>{{ __('user::ui.manager.filters.open') }}</span>
                    @if($this->activeFilterCount() > 0)
                        <x-ui::badge :value="$this->activeFilterCount()" variant="info" class="badge-sm" />
                    @endif
                </x-ui::button>
            </x-slot:trigger>

            <div class="w-[min(92vw,30rem)] space-y-4 p-2">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <x-ui::select
                        :label="__('user::ui.manager.filters.role')"
                        icon="tabler.shield"
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

                <div class="flex justify-end">
                    <x-ui::button
                        :label="__('user::ui.manager.filters.reset')"
                        icon="tabler.filter-off"
                        variant="secondary"
                        wire:click="resetFilters"
                    />
                </div>
            </div>
        </x-ui::dropdown>
    </x-slot:filters>

    <x-slot:rowActions></x-slot:rowActions>
</x-ui::record-manager>
