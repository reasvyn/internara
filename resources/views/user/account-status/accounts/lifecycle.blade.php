<div class="p-8">
    <x-ui::components.page-header :title="__('auth.lifecycle.title')" :description="__('auth.lifecycle.subtitle')">
        <x-slot:actions>
            <x-ts-button
                :text="__('auth.lifecycle.detect_clones')"
                icon="user-group"
                color="secondary"
                href="{{ route('admin.accounts.clones') }}"
            />
        </x-slot:actions>
    </x-ui::components.page-header>

    <x-ts-card class="bg-base-100 border-base-200 border">
        @if ($users->isEmpty())
            <div class="py-8 text-center opacity-60">
                <x-ts-icon name="users" class="mx-auto mb-3 h-12 w-12" />
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

            <x-ts-table :headers="$headers" :rows="$users" paginate>
                @interact('column_name', $user)
                    <div>
                        <div class="font-medium">{{ $user->name }}</div>
                        <div class="text-xs opacity-50">{{ $user->email }}</div>
                    </div>
                @endinteract

                @interact('column_status', $user)
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
                    <x-ts-badge :text="ucfirst($status)" :class="$color" />
                @endinteract

                @interact('column_locked', $user)
                    @if ($user->locked_at)
                        <x-ts-badge :text="__('auth.lifecycle.locked')" class="badge-error" />
                    @else
                        <x-ts-badge :text="__('auth.lifecycle.unlocked')" class="badge-success" />
                    @endif
                @endinteract

                @interact('column_created_at', $user)
                    {{ $user->created_at->format('d M Y') }}
                @endinteract

                @interact('column_action', $user)
                    <div class="flex gap-2">
                        @if ($user->locked_at)
                            <x-ts-button
                                aria-label="{{ __('common.actions.unlock') }}"
                                icon="lock-open"
                                class="text-success"
                                color="white"
                                sm
                                wire:click="askUnlock('{{ $user->id }}')"
                            />
                        @else
                            <x-ts-button
                                aria-label="{{ __('common.actions.lock') }}"
                                icon="lock-closed"
                                class="text-warning"
                                color="white"
                                sm
                                wire:click="askLock('{{ $user->id }}')"
                            />
                        @endif
                    </div>
                @endinteract
            </x-ts-table>
        @endif

        @include('user.account-status.components.account-lifecycle-guide')
</div>
