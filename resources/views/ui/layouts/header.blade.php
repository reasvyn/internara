@props([
    'header' => null,
    'subheader' => null,
    'breadcrumbs' => null, // array of ['label' => '', 'url' => '']
    'sticky' => true,
])

{{-- Modern responsive Header — props-driven, no hardcoded menu. --}}
<header
    {{
        $attributes->merge([
            'class' => collect([
                'flex h-16 shrink-0 items-center justify-between gap-4 border-b bg-base-100 border-base-content/10 px-4 sm:px-6 lg:px-8',
                $sticky ? 'sticky top-0 z-20 backdrop-blur supports-[backdrop-filter]:bg-base-100/80' : '',
            ])->filter()->implode(' '),
        ])
    }}
    role="banner"
>
    {{-- Left: mobile menu toggle + title/breadcrumbs --}}
    <div class="flex min-w-0 flex-1 items-center gap-3">
        {{-- Mobile sidebar toggle — controls sidebar drawer via Alpine store --}}
        <button
            type="button"
            class="btn btn-ghost btn-sm -ml-2 lg:hidden"
            @click="$dispatch('toggle-sidebar')"
            aria-label="{{ __('common.menu') }}"
            aria-controls="app-sidebar"
            aria-expanded="false"
        >
            <x-ts-icon name="bars-3" class="size-5" />
        </button>

        <div class="min-w-0 flex-1">
            @if ($breadcrumbs)
                <nav
                    aria-label="Breadcrumb"
                    class="text-base-content/60 mb-0.5 hidden items-center gap-1.5 text-xs sm:flex"
                >
                    @foreach ($breadcrumbs as $crumb)
                        @if (! $loop->last && ! empty($crumb['url']))
                            <a
                                href="{{ $crumb['url'] }}"
                                wire:navigate
                                class="hover:text-primary truncate transition-colors"
                            >{{ $crumb['label'] }}</a>
                            <x-ts-icon name="chevron-right" class="size-3 shrink-0 opacity-40" />
                        @else
                            <span class="text-primary truncate font-medium">{{ $crumb['label'] }}</span>
                        @endif
                    @endforeach
                </nav>
            @endif

            @if ($header)
                <h1 class="truncate text-lg leading-tight font-semibold">{{ $header }}</h1>
                @if ($subheader)
                    <p class="text-base-content/60 truncate text-xs">{{ $subheader }}</p>
                @endif
            @else
                <div class="flex items-center gap-2 lg:hidden">
                    <a wire:navigate href="{{ route('dashboard') }}" aria-label="{{ brand('name') }}">
                        <x-ui::components.logo size="5" />
                    </a>
                    <span class="text-sm font-bold">{{ brand('name') }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Right: actions + navbar-actions --}}
    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
        @isset($actions)
            <div class="hidden items-center gap-2 sm:flex">{{ $actions }}</div>
        @endisset

        <x-ui::components.navbar-actions
            :show-theme="true"
            :show-language="true"
            :show-notifications="true"
            :show-user="true"
        />

        @isset($actions)
            <div class="sm:hidden">
                <x-ts-dropdown position="bottom-end">
                    <x-slot:action>
                        <button
                            type="button"
                            class="btn btn-ghost btn-sm"
                            aria-label="{{ __('common.actions.label') }}"
                        >
                            <x-ts-icon name="ellipsis-vertical" class="size-5" />
                        </button>
                    </x-slot:action>
                    <div class="p-2">{{ $actions }}</div>
                </x-ts-dropdown>
            </div>
        @endisset
    </div>
</header>
