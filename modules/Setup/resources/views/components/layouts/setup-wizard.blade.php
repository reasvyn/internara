@props([
    'header' => null,
    'content' => null,
])

<div class="flex min-h-screen w-full flex-col items-center py-12 md:py-24 px-6 md:px-12 bg-base-100/50">
    <div class="w-full max-w-6xl flex flex-col gap-16 md:gap-24">
        <!-- Header Section: Full Width & Centered -->
        <header class="w-full animate-fade-in">
            {{ $header }}
        </header>

        <!-- Content Section: Full Width (Adaptive for Tables/Forms) -->
        @isset($content)
            <main class="w-full animate-fade-in-up" style="animation-delay: 100ms;">
                {{ $content }}
            </main>
        @endisset
    </div>
</div>
