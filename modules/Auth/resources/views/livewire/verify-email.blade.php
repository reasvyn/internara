<x-ui::card
    class="w-full max-w-lg text-center"
    title="{{ __('Email Verification') }}"
    subtitle="{{ __('Please verify your email address to access all features.') }}"
>
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <x-ui::alert type="success" :description="session('status')" />
        @endif

        @if (session('error'))
            <x-ui::alert type="error" :description="session('error')" />
        @endif

        <p class="text-base-content/70">
            {{ __('Before proceeding, please check your email for a verification link.') }}
        </p>

        <x-ui::form wire:submit="verify">
            <x-ui::button
                priority="primary"
                class="w-full"
                label="{{ __('Verify Email') }}"
                type="submit"
                spinner
            />
        </x-ui::form>

        <div class="text-center text-sm text-base-content/60">
            {{ __("Didn't receive the email?") }}
            <button
                wire:click="resend"
                class="font-medium text-accent underline hover:text-accent/80"
            >
                {{ __('Click here to resend') }}
            </button>
        </div>
    </div>
</x-ui::card>
