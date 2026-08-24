<x-ts-modal wire="showAccountSlip" :title="__('user.manager.account_slip')" separator blur size="sm">
    @if ($slipUser)
        <div class="space-y-5">
            <div class="bg-base-200/40 border-base-content/10 space-y-3 rounded-xl border p-5">
                <div>
                    <p class="text-base-content/40 text-xs font-semibold tracking-wider uppercase">
                        {{ __('sysadmin.account_slip.name') }}
                    </p>
                    <p class="font-semibold">{{ $slipUser->name }}</p>
                </div>
                <div>
                    <p class="text-base-content/40 text-xs font-semibold tracking-wider uppercase">
                        {{ __('sysadmin.account_slip.username') }}
                    </p>
                    <p class="font-mono text-sm">{{ $slipUser->username }}</p>
                </div>
                <div>
                    <p class="text-base-content/40 text-xs font-semibold tracking-wider uppercase">
                        {{ __('sysadmin.account_slip.email') }}
                    </p>
                    <p class="text-sm">{{ $slipUser->email }}</p>
                </div>
            </div>

            <div class="bg-primary/5 border-primary/20 rounded-xl border p-5 text-center">
                <p class="text-base-content/40 mb-2 text-xs font-semibold tracking-wider uppercase">
                    {{ __('sysadmin.account_slip.activation_code') }}
                </p>
                <p class="text-primary font-mono text-2xl font-bold tracking-[0.25em] select-all">{{ $slipCode }}</p>
                <p class="text-base-content/40 mt-2 text-[10px]">
                    {{ __('sysadmin.account_slip.code_expiry', ['days' => 30]) }}
                </p>
            </div>

            <div class="flex flex-col gap-2">
                <x-ts-button
                    :text="__('user.manager.download_slip')"
                    icon="arrow-down-tray"
                    class="w-full"
                    color="primary"
                    wire:click="downloadSlip"
                    loading="downloadSlip"
                />
                <div class="flex gap-2">
                    <x-ts-button
                        :text="__('user.manager.regenerate_code')"
                        icon="arrow-path"
                        class="flex-1"
                        color="white"
                        wire:click="regenerateCode"
                        loading="regenerateCode"
                    />
                    <x-ts-button
                        :text="__('user.manager.send_code')"
                        icon="envelope"
                        class="flex-1"
                        color="white"
                        wire:click="sendCode"
                        loading="sendCode"
                    />
                </div>
            </div>
        </div>
    @endif

    <x-slot:footer>
        <x-ts-button :text="__('common.actions.close')" wire:click="$set('showAccountSlip', false)" color="white" sm />
    </x-slot:footer>
</x-ts-modal>
