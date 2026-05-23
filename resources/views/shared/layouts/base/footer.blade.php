@props(['fullWidth' => false])

<footer class="bg-base-100 border-t border-base-content/10 py-6 mt-auto">
    <div @class([
        'mx-auto px-4 sm:px-6 lg:px-8',
        'container max-w-7xl' => !$fullWidth,
    ])>
        <x-shared::ui.credit :show-version="true" class="justify-center" />
    </div>
</footer>
