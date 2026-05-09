<div>
    <x-mary-header title="Recovery Code" subtitle="Generate a one-time recovery code for account recovery" separator progress-indicator />

    <div class="max-w-2xl mx-auto">
        <x-mary-card shadow class="card-enterprise rounded-[2rem] border-base-200">
            @if($generatedCode)
                <div class="text-center space-y-6">
                    <div class="size-20 rounded-3xl bg-success/10 text-success flex items-center justify-center mx-auto">
                        <x-mary-icon name="o-document-text" class="size-10" />
                    </div>

                    <div>
                        <h3 class="text-xl font-black tracking-tight">Recovery Code Generated</h3>
                        <p class="text-sm text-base-content/60 mt-2">
                            Save this code immediately. It will not be shown again.
                        </p>
                    </div>

                    <div class="bg-base-200 rounded-2xl p-6">
                        <p class="text-3xl font-mono font-black tracking-[0.3em] select-all">{{ $generatedCode }}</p>
                    </div>

                    <div class="bg-warning/5 border border-warning/20 rounded-2xl p-4 text-left">
                        <p class="text-xs font-bold uppercase tracking-widest text-warning">Important</p>
                        <ul class="text-xs text-base-content/60 mt-2 space-y-1">
                            <li class="flex items-start gap-2">
                                <x-mary-icon name="o-clock" class="size-3 mt-0.5 shrink-0" />
                                This code expires on <strong>{{ $expiresAt }}</strong>
                            </li>
                            <li class="flex items-start gap-2">
                                <x-mary-icon name="o-shield-exclamation" class="size-3 mt-0.5 shrink-0" />
                                It can only be used once. Generating a new code will replace this one.
                            </li>
                            <li class="flex items-start gap-2">
                                <x-mary-icon name="o-eye-slash" class="size-3 mt-0.5 shrink-0" />
                                Store it securely. Do not share it with anyone.
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col gap-3">
                        <x-mary-button
                            label="Generate New Code"
                            icon="o-arrow-path"
                            class="btn-primary w-full rounded-2xl font-bold uppercase tracking-widest"
                            wire:click="resetCode"
                        />
                    </div>
                </div>
            @else
                <div class="text-center space-y-6">
                    <div class="size-20 rounded-3xl bg-base-200 text-base-content/40 flex items-center justify-center mx-auto">
                        <x-mary-icon name="o-key" class="size-10" />
                    </div>

                    <div>
                        <h3 class="text-xl font-black tracking-tight">Self-Service Recovery Code</h3>
                        <p class="text-sm text-base-content/60 mt-2">
                            Generate a one-time code to recover your account if you ever lose access. 
                            Keep it in a safe place.
                        </p>
                    </div>

                    <div class="bg-info/5 border border-info/20 rounded-2xl p-4 text-left">
                        <p class="text-xs font-bold uppercase tracking-widest text-info">How it works</p>
                        <ol class="text-xs text-base-content/60 mt-2 space-y-1 list-decimal list-inside">
                            <li>Click "Generate Recovery Code" below</li>
                            <li>Save the code immediately (it will not be shown again)</li>
                            <li>Use it at the <a href="{{ route('recover.account') }}" class="text-primary hover:underline" wire:navigate>Account Recovery</a> page if you lose access</li>
                        </ol>
                    </div>

                    <x-mary-button
                        label="Generate Recovery Code"
                        icon="o-document-plus"
                        class="btn-primary w-full rounded-2xl font-bold uppercase tracking-widest"
                        wire:click="generate"
                        spinner="generate"
                    />
                </div>
            @endif
        </x-mary-card>

        <div class="mt-6 text-center">
            <a href="{{ route('profile') }}" class="text-xs font-bold uppercase tracking-widest text-base-content/40 hover:text-primary" wire:navigate>
                <x-mary-icon name="o-arrow-left" class="size-3 mr-1" />
                Back to Profile
            </a>
        </div>
    </div>
</div>
