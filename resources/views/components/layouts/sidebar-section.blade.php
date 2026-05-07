@props(['title'])

<div x-data="{ open: true }">
    <button
        @click="open = !open"
        class="w-full flex items-center justify-between px-3 sm:px-4 py-2 text-[9px] sm:text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 hover:text-base-content/50 transition-colors"
    >
        <span>{{ $title }}</span>
        <svg
            class="size-3 transition-transform duration-200"
            :class="{ 'rotate-180': open }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <ul x-show="open" x-collapse x-cloak class="space-y-1 px-1">
        {{ $slot }}
    </ul>
</div>
