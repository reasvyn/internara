@props([
    'headers' => [],
    'rows' => null,
    'paginate' => false,
    'simple' => false,
])

{{--
    UI Wrapper: Table
    Wraps TallStackUI table (<x-ts-table>) inside the canonical Internara container
    (overflow-x-auto rounded-2xl border border-base-content/10 bg-base-100) per UI/UX guidelines.
--}}
<div {{ $attributes->only('class')->class(['overflow-x-auto rounded-2xl border border-base-content/10 bg-base-100 shadow-2xs']) }}>
    <x-ts-table
        :headers="$headers"
        :rows="$rows"
        :paginate="$paginate"
        :simple="$simple"
        {{ $attributes->except('class') }}
    >
        {{ $slot }}
    </x-ts-table>
</div>
