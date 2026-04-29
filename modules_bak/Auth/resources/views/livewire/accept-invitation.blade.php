<div class="flex w-full items-center justify-center">
    <x-ui::card
        wire:key="accept-invitation-card"
        class="w-full max-w-lg text-center"
        :title="__('auth::invitation.title')"
        :subtitle="$invalidToken ? __('auth::invitation.subtitle_invalid') : __('auth::invitation.subtitle', ['name' => $userName])"
    >

        {{-- Invalid / expired token --}}
        @if ($invalidToken)
            <div class="flex flex-col items-center gap-6 py-4">
                <x-ui::icon name="tabler.link-off" class="size-16 text-error/60" />

                <x-ui::alert type="error" icon="tabler.alert-circle">
                    {{ __('auth::invitation.invalid_message') }}
                </x-ui::alert>

                <a href="{{ route('login') }}" wire:navigate
                   class="btn btn-primary w-full">
                    ← {{ __('auth::invitation.back_to_login') }}
                </a>
            </div>

        {{-- Valid token: show password form --}}
        @else
            <div class="flex flex-col gap-8">
                <x-ui::alert type="info" icon="tabler.shield-lock" class="text-start text-sm">
                    {{ __('auth::invitation.info') }}
                </x-ui::alert>

                <x-ui::form class="text-start" wire:submit="accept">
                    <div wire:key="inv-password">
                        <x-ui::input
                            type="password"
                            icon="tabler.lock"
                            :label="__('auth::invitation.form.password')"
                            :placeholder="__('auth::invitation.form.password_placeholder')"
                            wire:model="password"
                            autofocus
                            required
                        />
                    </div>

                    <div wire:key="inv-password-conf">
                        <x-ui::input
                            type="password"
                            icon="tabler.lock-check"
                            :label="__('auth::invitation.form.password_confirmation')"
                            :placeholder="__('auth::invitation.form.password_confirmation_placeholder')"
                            wire:model="password_confirmation"
                            required
                        />
                    </div>

                    <div class="mt-2 w-full" wire:key="inv-submit">
                        <x-ui::button
                            variant="primary"
                            class="w-full"
                            :label="__('auth::invitation.form.submit')"
                            type="submit"
                            spinner="accept"
                        />
                    </div>
                </x-ui::form>
            </div>
        @endif

    </x-ui::card>
</div>
