<x-pulse::card wire:poll.5s="">
    <x-pulse::card-header name="System">
        <x-slot:icon>
            <x-pulse::icons.server />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        <div class="grid grid-cols-2 gap-4 p-4">
            <div class="flex flex-col items-center rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $users }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Users</span>
            </div>
            <div class="flex flex-col items-center rounded-lg bg-orange-50 p-4 dark:bg-orange-900/20">
                <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $unreadNotifications }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unread Notifications</span>
            </div>
        </div>
    </x-pulse::scroll>
</x-pulse::card>
