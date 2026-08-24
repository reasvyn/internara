<div class="p-8">
    <x-mary-header
        :title="__('auth.recovery_slip.title')"
        :subtitle="__('auth.recovery_slip.subtitle')"
        separator
        progress-indicator
    />

    <div class="mx-auto max-w-2xl">
        <x-mary-card shadow class="card-enterprise">
            @if ($generatedCode)
                <div class="space-y-6 text-center">
                    <div class="bg-success/10 text-success mx-auto flex size-20 items-center justify-center rounded-3xl">
                        <x-ts-icon name="document-text" class="size-10" />
                    </div>
                    <div>
                        <h3 class="text-xl font-black tracking-tight">
                            {{ __('auth.recovery_slip.generated_title') }}
                        </h3>
                        <p class="text-base-content/60 mt-2 text-sm">{{ __('auth.recovery_slip.generated_desc') }}</p>
                    </div>
                    <div class="bg-base-200 rounded-2xl p-6">
                        @foreach ($generatedCode as $code)
                            <p class="font-mono text-xl font-black tracking-[0.3em] select-all">{{ $code }}</p>
                        @endforeach
                    </div>
                    <div class="bg-warning/5 border-warning/20 rounded-2xl border p-4 text-left">
                        <p class="text-warning text-xs font-bold tracking-widest uppercase">
                            {{ __('auth.recovery_slip.security_note') }}
                        </p>
                        <p class="text-base-content/60 mt-1 text-xs">
                            {{ __('auth.recovery_slip.security_note_desc') }}
                        </p>
                    </div>
                    <x-ts-button
                        :text="__('auth.recovery_slip.generate_another')"
                        icon="plus"
                        color="primary"
                        wire:click="resetForm"
                    />
                </div>
            @else
                <div class="space-y-6">
                    <x-ts-input
                        wire:model.live.debounce.300ms="search"
                        :label="__('auth.recovery_slip.search_user')"
                        :placeholder="__('auth.recovery_slip.search_placeholder')"
                        icon="magnifying-glass"
                        class="rounded-2xl"
                    />

                    @if ($search)
                        <div class="space-y-2">
                            @forelse ($users as $user)
                                <div
                                    wire:key="user-{{ $user->id }}"
                                    wire:click="selectUser({{ Js::from($user->id) }})"
                                    class="flex items-center gap-4 p-4 rounded-2xl border transition-all cursor-pointer {{ $selectedUser?->id === $user->id ? 'border-primary bg-primary/5' : 'border-base-content/5 hover:bg-base-200' }}"
                                >
                                    <x-core::ui.avatar :user="$user" size="size-10" />
                                    <div>
                                        <p class="text-sm font-bold">{{ $user->name }}</p>
                                        <p class="text-base-content/50 text-xs">
                                            {{ $user->username }}
                                            @if ($user->email) &middot;{{ $user->email }} @endif
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-base-content/40 py-8 text-center text-sm">
                                    {{ __('auth.recovery_slip.no_users_found') }}
                                </p>
                            @endforelse
                        </div>
                    @endif

                    @if ($selectedUser)
                        <div class="bg-primary/5 border-primary/20 rounded-2xl border p-4">
                            <p class="text-sm font-bold">
                                {{ __('auth.recovery_slip.selected_user', ['name' => $selectedUser->name, 'username' => $selectedUser->username]) }}
                            </p>
                        </div>

                        <div class="border-base-content/5 border-t pt-4">
                            <x-ts-button
                                :text="__('auth.recovery_slip.generate_slip')"
                                icon="document-plus"
                                class="w-full"
                                color="primary"
                                wire:click="generate"
                                loading="generate"
                            />
                        </div>
                    @endif
                </div>
            @endif
        </x-mary-card>

        <div class="mt-6 text-center">
            <a
                href="{{ route('sysadmin.dashboard') }}"
                class="text-base-content/40 hover:text-primary text-xs font-bold tracking-widest uppercase"
                wire:navigate
            >{{ __('auth.recovery_slip.back_to_dashboard') }}</a>
        </div>
    </div>

    @include('auth.account-recovery.components.recovery-slip-manager-guide')
</div>
