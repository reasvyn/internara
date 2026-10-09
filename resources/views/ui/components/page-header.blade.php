@props([
    'title' => '',
    'description' => null,
    'actions' => null,
])

<div class="mb-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-black tracking-tight">{{ $title }}</h1>
            @if ($description)
                <p class="dark:text-dark-400 mt-1 text-sm text-gray-500">{{ $description }}</p>
            @endif
        </div>
        @if ($actions)
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">{{ $actions }}</div>
        @endif
    </div>
</div>
