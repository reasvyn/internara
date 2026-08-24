<div class="p-8">
    <x-mary-header
        :title="__('auth.lifecycle.title')"
        :subtitle="__('auth.lifecycle.subtitle')"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-mary-button
                :label="__('auth.lifecycle.detect_clones')"
                icon="o-user-group"
                class="btn-secondary"
                href="{{ route('admin.accounts.clones') }}"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border-base-200 border">
        @if ($users->isEmpty())
            <div class="py-8 text-center opacity-60">
                <x-mary-icon name="o-users" class="mx-auto mb-3 h-12 w-12" />
                <p class="text-lg">{{ __('auth.recovery_slip.no_users_found') }}</p>
            </div>
        @else
            @php
                $headers = [
                    ['key' => 'name', 'label' => __('auth.lifecycle.user')],
                    ['key' => 'status', 'label' => __('auth.lifecycle.status')],
                    ['key' => 'locked', 'label' => __('auth.lifecycle.locked')],
                    ['key' => 'created_at', 'label' => __('auth.lifecycle.created')],
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
                        <x-mary-badge :value="__('auth.lifecycle.locked')" class="badge-error" />
                    @else
                        <x-mary-badge :value="__('auth.lifecycle.unlocked')" class="badge-success" />
                    @endif
                @endscope

                @scope('cell_created_at', $user)
                    {{ $user->created_at->format('d M Y') }}
                @endscope

                @scope('cell_actions', $user)
                    <div class="flex gap-2">
                        @if ($user->locked_at)
                            <x-mary-button
                                aria-label="{{ __('common.actions.unlock') }}"
                                icon="o-lock-open"
                                class="btn-ghost btn-sm text-success"
                                wire:click="askUnlock('{{ $user->id }}')"
                            />
                        @else
                            <x-mary-button
                                aria-label="{{ __('common.actions.lock') }}"
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

    @include('user.account-status.components.account-lifecycle-guide')
</div>
