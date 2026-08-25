@props(['fullWidth' => false])

<footer class="bg-base-100 border-base-content/10 mt-auto border-t py-6">
    <div @class([
        'mx-auto px-4 sm:px-6 lg:px-8',
        'container max-w-7xl' => ! $fullWidth,
    ])>
        <x-core::ui.credit :show-version="app()->environment('local')" class="justify-center" />
    </div>
</footer>
