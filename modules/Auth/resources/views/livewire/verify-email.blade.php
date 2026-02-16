<x-ui::card
    class="w-full max-w-lg text-center"
    :title="__('auth::ui.verification.title')"
    :subtitle="__('auth::ui.verification.subtitle')"
>
    <div class="flex flex-col gap-6">
        @if (session('status'))
            <x-ui::alert type="success" :description="session('status')" />
        @endif

        @if (session('error'))
            <x-ui::alert type="error" :description="session('error')" />
        @endif

        <p class="text-base-content/70">
            {{ __('auth::ui.verification.notice') }}
        </p>

        <x-ui::form wire:submit="verify">
            <x-ui::button
                variant="primary"
                class="w-full"
                :label="__('auth::ui.verification.verify_button')"
                type="submit"
                spinner
            />
        </x-ui::form>

        <div class="text-center text-sm text-base-content/60">
            {{ __('auth::ui.verification.resend_prompt') }}
            <button
                wire:click="resend"
                class="font-medium text-accent underline hover:text-accent/80"
            >
                {{ __('auth::ui.verification.resend_button') }}
            </button>
        </div>
    </div>
</x-ui::card>
