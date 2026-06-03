<x-mary-modal wire:model="showAccountSlip" :title="__('user.manager.account_slip')" separator class="backdrop-blur-sm" size="sm">
    @if($slipUser)
        <div class="space-y-5">
            <div class="bg-base-200/40 border border-base-content/10 rounded-xl p-5 space-y-3">
                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wider font-semibold">{{ __('admin.account_slip.name') }}</p>
                    <p class="font-semibold">{{ $slipUser->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wider font-semibold">{{ __('admin.account_slip.username') }}</p>
                    <p class="font-mono text-sm">{{ $slipUser->username }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wider font-semibold">{{ __('admin.account_slip.email') }}</p>
                    <p class="text-sm">{{ $slipUser->email }}</p>
                </div>
            </div>

            <div class="bg-primary/5 border border-primary/20 rounded-xl p-5 text-center">
                <p class="text-xs text-base-content/40 uppercase tracking-wider font-semibold mb-2">{{ __('admin.account_slip.activation_code') }}</p>
                <p class="text-2xl font-bold tracking-[0.25em] text-primary font-mono select-all">{{ $slipCode }}</p>
                <p class="text-[10px] text-base-content/40 mt-2">{{ __('admin.account_slip.code_expiry', ['days' => 30]) }}</p>
            </div>

            <div class="flex flex-col gap-2">
                <x-mary-button :label="__('user.manager.download_slip')" icon="o-arrow-down-tray" class="btn-primary w-full" wire:click="downloadSlip" spinner="downloadSlip" />
                <div class="flex gap-2">
                    <x-mary-button :label="__('user.manager.regenerate_code')" icon="o-arrow-path" class="btn-ghost flex-1" wire:click="regenerateCode" spinner="regenerateCode" />
                    <x-mary-button :label="__('user.manager.send_code')" icon="o-envelope" class="btn-ghost flex-1" wire:click="sendCode" spinner="sendCode" />
                </div>
            </div>
        </div>
    @endif

    <x-slot:actions>
        <x-mary-button :label="__('common.actions.close')" wire:click="$set('showAccountSlip', false)" class="btn-ghost btn-sm" />
    </x-slot:actions>
</x-mary-modal>
