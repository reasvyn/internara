<x-ui::button 
    label="{{ __('Presensi') }}" 
    icon="tabler.fingerprint" 
    link="{{ route('attendance.index') }}" 
    class="btn-ghost btn-sm {{ request()->routeIs('attendance.*') ? 'btn-active' : '' }}" 
/>
