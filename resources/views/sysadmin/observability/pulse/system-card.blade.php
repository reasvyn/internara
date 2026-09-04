<x-pulse::card wire:poll.5s="">
    <x-pulse::card-header name="{{ __('sysadmin.pulse.system') }}">
        <x-slot:icon>
            <x-pulse::icons.server />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        <div class="grid grid-cols-2 gap-4 p-4">
            <div class="bg-base-200 flex flex-col items-center rounded-lg p-4">
                <span class="text-base-content text-2xl font-bold">{{ $users }}</span>
                <span class="text-base-content/50 mt-1 text-xs">{{ __('sysadmin.pulse.users') }}</span>
            </div>
            <div class="bg-warning/10 flex flex-col items-center rounded-lg p-4">
                <span class="text-warning text-2xl font-bold">{{ $unreadNotifications }}</span>
                <span class="text-base-content/50 mt-1 text-xs">{{ __('sysadmin.pulse.unread_notifications') }}</span>
            </div>
        </div>
    </x-pulse::scroll>
</x-pulse::card>
