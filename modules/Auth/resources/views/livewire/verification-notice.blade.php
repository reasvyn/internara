<x-ui::card
    class="w-full max-w-lg text-center"
    title="{{ __('Verify Your Email') }}"
    subtitle="{{ __('Check your inbox for a verification link.') }}"
>
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <x-ui::alert type="success" :description="session('status')" />
        @endif

        <p class="text-base-content/70">
            {{ __('Before proceeding, please check your email for a verification link.') }}
        </p>

        <div class="text-center text-sm text-base-content/60">
            {{ __("Didn't receive the email?") }}
            <button
                wire:click="resend"
                class="font-medium text-accent underline hover:text-accent/80"
            >
                {{ __('Click here to request another') }}
            </button>
        </div>
    </div>
</x-ui::card>
