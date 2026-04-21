<div class="flex w-full items-center justify-center">
    <x-ui::card
        wire:key="claim-account-card"
        class="w-full max-w-lg text-center"
        :title="__('auth::claim.title')"
        :subtitle="$step === 1 ? __('auth::claim.subtitle_step1') : __('auth::claim.subtitle_step2')"
    >
        {{-- Progress indicator --}}
        <div class="mb-6 flex justify-center gap-2">
            @foreach ([1 => 'auth::claim.step_verify', 2 => 'auth::claim.step_password'] as $n => $label)
                <div class="flex items-center gap-1">
                    <div @class([
                        'flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold transition-colors',
                        'bg-primary text-primary-content' => $step >= $n,
                        'bg-base-200 text-base-content/40' => $step < $n,
                    ])>{{ $n }}</div>
                    <span @class([
                        'text-xs hidden sm:inline transition-colors',
                        'text-primary font-medium' => $step >= $n,
                        'text-base-content/40' => $step < $n,
                    ])>{{ __($label) }}</span>
                </div>
                @if ($n < 2)
                    <div @class([
                        'self-center w-8 h-px transition-colors',
                        'bg-primary' => $step > $n,
                        'bg-base-200' => $step <= $n,
                    ])></div>
                @endif
            @endforeach
        </div>

        <div class="flex flex-col gap-8">

            {{-- Step 1: username + activation code --}}
            @if ($step === 1)
                <x-ui::form class="text-start" wire:submit="verify">
                    <div wire:key="claim-username">
                        <x-ui::input
                            wire:model="username"
                            icon="tabler.at"
                            :label="__('auth::claim.form.username')"
                            :placeholder="__('auth::claim.form.username_placeholder')"
                            autofocus
                            required
                        />
                    </div>

                    <div wire:key="claim-code">
                        <x-ui::input
                            wire:model="activation_code"
                            icon="tabler.key"
                            :label="__('auth::claim.form.activation_code')"
                            :placeholder="__('auth::claim.form.code_placeholder')"
                            :hint="__('auth::claim.form.code_hint')"
                            required
                        />
                    </div>

                    <x-ui::alert type="info" icon="tabler.info-circle" class="text-start text-sm">
                        {{ __('auth::claim.info_step1') }}
                    </x-ui::alert>

                    <div class="mt-2 flex w-full flex-col gap-4" wire:key="claim-step1-actions">
                        <x-ui::button
                            variant="primary"
                            class="w-full"
                            :label="__('auth::claim.form.verify')"
                            type="submit"
                            spinner="verify"
                        />

                        <a href="{{ route('login') }}" wire:navigate
                           class="text-sm text-base-content/60 hover:text-base-content transition-colors">
                            ← {{ __('auth::claim.back_to_login') }}
                        </a>
                    </div>
                </x-ui::form>
            @endif

            {{-- Step 2: set personal password --}}
            @if ($step === 2)
                <x-ui::form class="text-start" wire:submit="claim">
                    <x-ui::alert type="success" icon="tabler.circle-check" class="text-start text-sm">
                        {{ __('auth::claim.code_verified') }}
                    </x-ui::alert>

                    <div wire:key="claim-new-password">
                        <x-ui::input
                            type="password"
                            icon="tabler.lock"
                            :label="__('auth::claim.form.password')"
                            :placeholder="__('auth::claim.form.password_placeholder')"
                            wire:model="password"
                            autofocus
                            required
                        />
                    </div>

                    <div wire:key="claim-password-conf">
                        <x-ui::input
                            type="password"
                            icon="tabler.lock-check"
                            :label="__('auth::claim.form.password_confirmation')"
                            :placeholder="__('auth::claim.form.password_confirmation_placeholder')"
                            wire:model="password_confirmation"
                            required
                        />
                    </div>

                    <x-ui::alert type="info" icon="tabler.shield-lock" class="text-start text-sm">
                        {{ __('auth::claim.info_step2') }}
                    </x-ui::alert>

                    <div class="mt-2 w-full" wire:key="claim-step2-actions">
                        <x-ui::button
                            variant="primary"
                            class="w-full"
                            :label="__('auth::claim.form.activate')"
                            type="submit"
                            spinner="claim"
                        />
                    </div>
                </x-ui::form>
            @endif

        </div>
    </x-ui::card>
</div>
