<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('profile.recovery.title') }}</h2>
        <p class="text-sm text-base-content/50 mt-1">{{ __('profile.recovery.subtitle') }}</p>
    </div>

    <div class="max-w-lg mx-auto">
        <x-mary-card class="bg-base-100 border border-base-content/10">
            @if(!empty($codes))
                <div class="text-center space-y-5">
                    <div class="size-14 rounded-full bg-success/10 text-success flex items-center justify-center mx-auto">
                        <x-mary-icon name="o-document-text" class="size-7" />
                    </div>

                    <div>
                        <h3 class="font-semibold">{{ __('profile.recovery.generated_title') }}</h3>
                        <p class="text-xs text-base-content/50 mt-1">{{ __('profile.recovery.generated_desc') }}</p>
                    </div>

                    <div class="bg-base-200 rounded-xl p-5 text-left space-y-2">
                        @foreach($codes as $index => $code)
                            <div class="flex items-center gap-3 px-4 py-2.5 bg-base-100 rounded-lg border border-base-content/10 font-mono text-sm font-bold tracking-wider select-all">
                                <span class="text-xs text-base-content/30 font-mono w-6 shrink-0">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="flex-1">{{ $code }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-warning/5 border border-warning/20 rounded-xl p-4 text-left space-y-2 text-xs">
                        <p class="flex items-start gap-2"><x-mary-icon name="o-shield-exclamation" class="size-3 mt-0.5 shrink-0" /> {{ __('profile.recovery.one_time_per_code') }}</p>
                        <p class="flex items-start gap-2"><x-mary-icon name="o-eye-slash" class="size-3 mt-0.5 shrink-0" /> {{ __('profile.recovery.store_securely') }}</p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <x-mary-button :label="__('profile.recovery.download_pdf')" icon="o-arrow-down-tray" class="btn-primary w-full" wire:click="downloadPdf" spinner="downloadPdf" />
                        <x-mary-button :label="__('profile.recovery.generate_new')" icon="o-arrow-path" class="btn-ghost btn-sm" wire:click="resetCode" />
                    </div>
                </div>
            @else
                <div class="text-center space-y-5">
                    <div class="size-14 rounded-full bg-base-200 text-base-content/30 flex items-center justify-center mx-auto">
                        <x-mary-icon name="o-key" class="size-7" />
                    </div>

                    <div>
                        <h3 class="font-semibold">{{ __('profile.recovery.empty_title') }}</h3>
                        <p class="text-xs text-base-content/50 mt-1">{{ __('profile.recovery.empty_desc') }}</p>
                    </div>

                    <div class="bg-info/5 border border-info/20 rounded-xl p-4 text-left">
                        <p class="text-xs font-semibold text-info mb-2">{{ __('profile.recovery.how_it_works') }}</p>
                        <ol class="text-xs text-base-content/60 space-y-1 list-decimal list-inside">
                            <li>{{ __('profile.recovery.step_1') }}</li>
                            <li>{{ __('profile.recovery.step_2') }}</li>
                            <li>{{ __('profile.recovery.step_3') }} <a href="{{ route('recover.account') }}" class="text-primary hover:underline" wire:navigate>{{ __('profile.recovery.title') }}</a></li>
                        </ol>
                    </div>

                    <x-mary-button :label="__('profile.recovery.generate')" icon="o-document-plus" class="btn-primary w-full" wire:click="generate" spinner="generate" />
                </div>
            @endif
        </x-mary-card>

        <div class="mt-5 text-center">
            <a href="{{ route('profile') }}" class="text-xs text-base-content/50 hover:text-primary transition-colors" wire:navigate>
                <x-mary-icon name="o-arrow-left" class="size-3 mr-1" /> {{ __('profile.recovery.back') }}
            </a>
        </div>
    </div>
</div>
