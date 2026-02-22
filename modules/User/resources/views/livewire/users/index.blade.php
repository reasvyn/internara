<div>
    <x-ui::header
        :title="__('User Management')"
        :subtitle="__('Manage all system users and their roles.')"
        :context="'admin::ui.dashboard.user_management'"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-ui::button label="{{ __('New User') }}" link="/users/create" icon="tabler.plus" class="btn-primary" />
        </x-slot>
    </x-ui::header>

    <x-ui::card>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div class="w-full md:w-1/3">
                                    <x-ui::input
                                        placeholder="{{ __('Search users...') }}"
                                        wire:model.live.debounce="search"
                                        icon="tabler.search"
                                        clearable
                                    />            </div>
        </div>

        <x-ui::table
            :headers="$headers"
            :rows="$users"
            :sort-by="$sortBy"
            :sort-dir="$sortDir"
            with-pagination
        >
            @scope('cell_name', $user)
                <div class="flex items-center gap-3">
                    <x-ui::avatar :image="$user->avatar_url" class="!w-9" />
                    <div>
                        <div class="font-bold">{{ $user->name }}</div>
                        <div class="text-xs opacity-50">{{ $user->username }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_roles', $user)
                <div class="flex flex-wrap gap-1">
                    @foreach ($user->roles as $role)
                        <livewire:permission::role-badge
                            :role="$role"
                            size="xs"
                            :wire:key="'role-' . $user->id . '-' . $role->id"
                        />
                    @endforeach
                </div>
            @endscope

            @scope('cell_status', $user)
                <x-ui::badge
                    :label="$user->getStatusLabel()"
                    :class="$user->getStatusColor()"
                    class="badge-outline"
                />
            @endscope

            @scope('cell_created_at', $user)
                <span class="text-xs">
                    {{ $user->created_at?->format('d M Y') }}
                </span>
            @endscope

            @scope('actions', $user)
                <div class="flex items-center gap-2">
                    @if (! $user->hasRole('super-admin'))
                                <x-ui::button
                                    icon="{{ $user->latestStatus()?->name === 'active' ? 'tabler.lock' : 'tabler.lock-open' }}"
                                    wire:click="toggleStatus('{{ $user->id }}')"
                                    spinner
                                    class="btn-ghost btn-sm text-warning"
                                    title="{{ $user->latestStatus()?->name === 'active' ? __('Deactivate') : __('Activate') }}"
                                />
                    @endif

                                <x-ui::button
                                    icon="tabler.edit"
                                    link="/users/{{ $user->id }}/edit"
                                    class="btn-ghost btn-sm text-info"
                                />
                    @if (! $user->hasRole('super-admin'))
                                <x-ui::button
                                    icon="tabler.trash"
                                    wire:click="delete('{{ $user->id }}')"
                                    wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                    spinner
                                    class="btn-ghost btn-sm text-error"
                                />
                    @endif
                </div>
            @endscope
        </x-ui::table>
    </x-ui::card>
</div>
