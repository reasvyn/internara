@props([
    'title' => '',
    'description' => null,
    'actions' => null,
])

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black">{{ $title }}</h1>
            @if ($description)
                <p class="text-base-content/60 mt-1 text-sm">{{ $description }}</p>
            @endif
        </div>
        @if ($actions)
            <div>{{ $actions }}</div>
        @endif
    </div>
</div>
