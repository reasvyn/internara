<div class="p-8">
    <x-mary-header :title="__('auth.lifecycle.title')" :subtitle="__('auth.lifecycle.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Detect Clones" icon="o-user-group" class="btn-secondary" href="{{ route('sysadmin.accounts.detect-clones') }}" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        @if ($users->isEmpty())
            <div class="text-center py-8 opacity-60">
                <x-mary-icon name="o-users" class="w-12 h-12 mx-auto mb-3" />
                <p class="text-lg">No users found.</p>
            </div>
        @else
            @php
                $headers = [
                    ['key' => 'name', 'label' => 'User'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'locked', 'label' => 'Locked'],
                    ['key' => 'created_at', 'label' => 'Created'],
                    ['key' => 'actions', 'label' => ''],
                ];
            @endphp

            <x-mary-table :headers="$headers" :rows="$users" with-pagination>
                @scope('cell_name', $user)
                    <div>
                        <div class="font-medium">{{ $user->name }}</div>
                        <div class="text-xs opacity-50">{{ $user->email }}</div>
                    </div>
                @endscope

                @scope('cell_status', $user)
                    @php
                        $status = $user->status?->value ?? 'unknown';
                        $color = match ($status) {
                            'active' => 'badge-success',
                            'suspended' => 'badge-error',
                            'archived' => 'badge-neutral',
                            'inactive' => 'badge-warning',
                            default => 'badge-ghost',
                        };
                    @endphp
                    <x-mary-badge :value="ucfirst($status)" :class="$color" />
                @endscope

                @scope('cell_locked', $user)
                    @if ($user->locked_at)
                        <x-mary-badge value="Locked" class="badge-error" />
                    @else
                        <x-mary-badge value="Unlocked" class="badge-success" />
                    @endif
                @endscope

                @scope('cell_created_at', $user)
                    {{ $user->created_at->format('d M Y') }}
                @endscope

                @scope('cell_actions', $user)
                    <div class="flex gap-2">
                        @if ($user->locked_at)
                            <x-mary-button
                                icon="o-lock-open"
                                class="btn-ghost btn-sm text-success"
                                wire:click="askUnlock('{{ $user->id }}')"
                            />
                        @else
                            <x-mary-button
                                icon="o-lock-closed"
                                class="btn-ghost btn-sm text-warning"
                                wire:click="askLock('{{ $user->id }}')"
                            />
                        @endif
                    </div>
                @endscope
            </x-mary-table>
        @endif
    </x-mary-card>

    <x-core::ui.confirm :message="$confirmMessage" />
    @include('user.account-status.components.account-lifecycle-guide')
</div>
