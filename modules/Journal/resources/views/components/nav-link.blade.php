<x-ui::button 
    :label="__('journal::ui.index.nav_title')" 
    icon="tabler.book" 
    link="{{ route('journal.index') }}" 
    class="btn-ghost btn-sm {{ request()->routeIs('journal.*') ? 'btn-active' : '' }}" 
/>
