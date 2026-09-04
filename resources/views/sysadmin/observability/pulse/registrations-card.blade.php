<x-pulse::card wire:poll.5s="">
    <x-pulse::card-header name="{{ __('sysadmin.pulse.registrations') }}">
        <x-slot:icon>
            <x-pulse::icons.users />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        <div class="grid grid-cols-2 gap-4 p-4">
            <div class="bg-base-200 flex flex-col items-center rounded-lg p-4">
                <span class="text-base-content text-2xl font-bold">{{ $total }}</span>
                <span class="text-base-content/50 mt-1 text-xs">{{ __('sysadmin.pulse.total') }}</span>
            </div>
            <div class="bg-warning/10 flex flex-col items-center rounded-lg p-4">
                <span class="text-warning text-2xl font-bold">{{ $pending }}</span>
                <span class="text-base-content/50 mt-1 text-xs">{{ __('sysadmin.pulse.pending') }}</span>
            </div>
            <div class="bg-success/10 flex flex-col items-center rounded-lg p-4">
                <span class="text-success text-2xl font-bold">{{ $active }}</span>
                <span class="text-base-content/50 mt-1 text-xs">{{ __('sysadmin.pulse.active') }}</span>
            </div>
            <div class="bg-info/10 flex flex-col items-center rounded-lg p-4">
                <span class="text-info text-2xl font-bold">{{ $completed }}</span>
                <span class="text-base-content/50 mt-1 text-xs">{{ __('sysadmin.pulse.completed') }}</span>
            </div>
        </div>
    </x-pulse::scroll>
</x-pulse::card>
