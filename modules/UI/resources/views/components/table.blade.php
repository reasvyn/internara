@props(['aos' => null])

<div class="w-full overflow-x-auto rounded-xl border border-base-200 bg-base-100 shadow-sm" :data-aos="$aos">
    <table {{ $attributes->merge(['class' => 'table table-zebra table-md w-full']) }}>
        {{ $slot }}
    </table>
</div>
