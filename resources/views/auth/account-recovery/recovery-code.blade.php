<div>
    <div class="mb-6 flex items-center gap-4">
        <div class="bg-primary/10 text-primary flex size-12 shrink-0 items-center justify-center rounded-xl">
            <x-ts-icon name="key" class="size-6" />
        </div>
        <div>
            <h2 class="text-xl font-bold">{{ __('profile.recovery.title') }}</h2>
            <p class="text-base-content/60 mt-0.5 text-sm">{{ __('profile.recovery.subtitle') }}</p>
        </div>
    </div>

    <div class="mx-auto max-w-lg">
        <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
            @if (! empty($codes))
                <div class="space-y-5 text-center">
                    <div class="bg-success/10 text-success mx-auto flex size-14 items-center justify-center rounded-full">
                        <x-ts-icon name="document-text" class="size-7" />
                    </div>

                    <div>
                        <h3 class="font-semibold">{{ __('profile.recovery.generated_title') }}</h3>
                        <p class="text-base-content/60 mt-1 text-xs">{{ __('profile.recovery.generated_desc') }}</p>
                    </div>

                    <div class="bg-base-200 space-y-2 rounded-xl p-5 text-left">
                        @foreach ($codes as $index => $code)
                            <div class="bg-base-100 border-base-content/10 flex items-center gap-3 rounded-lg border px-4 py-2.5 font-mono text-sm font-bold tracking-wider select-all">
                                <span class="text-base-content/60 w-6 shrink-0 font-mono text-xs">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="flex-1">{{ $code }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-warning/5 border-warning/20 space-y-2 rounded-xl border p-4 text-left text-xs">
                        <p class="flex items-start gap-2">
                            <x-ts-icon name="shield-exclamation" class="mt-0.5 size-3 shrink-0" />
                            {{ __('profile.recovery.one_time_per_code') }}
                        </p>
                        <p class="flex items-start gap-2">
                            <x-ts-icon name="eye-slash" class="mt-0.5 size-3 shrink-0" />
                            {{ __('profile.recovery.store_securely') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <x-ts-button
                            :text="__('profile.recovery.download_pdf')"
                            icon="arrow-down-tray"
                            class="w-full"
                            color="primary"
                            wire:click="downloadPdf"
                            loading="downloadPdf"
                        />
                        <x-ts-button
                            :text="__('profile.recovery.generate_new')"
                            icon="arrow-path"
                            color="white"
                            sm
                            wire:click="resetCode"
                        />
                    </div>
                </div>
            @else
                <div class="space-y-5 text-center">
                    <div class="bg-base-200 text-base-content/30 mx-auto flex size-14 items-center justify-center rounded-full">
                        <x-ts-icon name="key" class="size-7" />
                    </div>

                    <div>
                        <h3 class="font-semibold">{{ __('profile.recovery.empty_title') }}</h3>
                        <p class="text-base-content/60 mt-1 text-xs">{{ __('profile.recovery.empty_desc') }}</p>
                    </div>

                    <div class="bg-info/5 border-info/20 rounded-xl border p-4 text-left">
                        <p class="text-info mb-2 text-xs font-semibold">{{ __('profile.recovery.how_it_works') }}</p>
                        <ol class="text-base-content/60 list-inside list-decimal space-y-1 text-xs">
                            <li>{{ __('profile.recovery.step_1') }}</li>
                            <li>{{ __('profile.recovery.step_2') }}</li>
                            <li>
                                {{ __('profile.recovery.step_3') }}
                                <a
                                    href="{{ route('recover.account') }}"
                                    class="text-primary hover:underline"
                                    wire:navigate
                                >{{ __('profile.recovery.title') }}</a>
                            </li>
                        </ol>
                    </div>

                    <x-ts-button
                        :text="__('profile.recovery.generate')"
                        icon="document-plus"
                        class="w-full"
                        color="primary"
                        wire:click="generate"
                        loading="generate"
                    />
                </div>
            @endif

            <div class="mt-6 text-center">
                <a
                    href="{{ route('profile') }}"
                    class="text-base-content/60 hover:text-primary inline-flex items-center gap-1.5 text-xs font-medium transition-colors"
                    wire:navigate
                >
                    <x-ts-icon name="arrow-left" class="size-3" /> {{ __('profile.recovery.back') }}
                </a>
            </div>
        </x-ts-card>
    </div>

    @include('auth.account-recovery.components.recovery-guide')
</div>
