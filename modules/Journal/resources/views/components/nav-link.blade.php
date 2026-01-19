<x-ui::button 
    label="{{ __('Jurnal') }}" 
    icon="tabler.book" 
    link="{{ route('journal.index') }}" 
    class="btn-ghost btn-sm {{ request()->routeIs('journal.*') ? 'btn-active' : '' }}" 
/>
