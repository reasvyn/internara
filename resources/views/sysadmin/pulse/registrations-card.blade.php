<x-pulse::card wire:poll.5s="">
    <x-pulse::card-header name="Registrations">
        <x-slot:icon>
            <x-pulse::icons.users />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        <div class="grid grid-cols-2 gap-4 p-4">
            <div class="flex flex-col items-center rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $total }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total</span>
            </div>
            <div class="flex flex-col items-center rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                <span class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pending }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pending</span>
            </div>
            <div class="flex flex-col items-center rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $active }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Active</span>
            </div>
            <div class="flex flex-col items-center rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $completed }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Completed</span>
            </div>
        </div>
    </x-pulse::scroll>
</x-pulse::card>
