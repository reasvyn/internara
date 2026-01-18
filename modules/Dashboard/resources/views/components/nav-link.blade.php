@php
    $routeName = 'dashboard';
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasRole('admin')) $routeName = 'dashboard.admin';
        elseif ($user->hasRole('teacher')) $routeName = 'dashboard.teacher';
        elseif ($user->hasRole('student')) $routeName = 'dashboard.student';
        elseif ($user->hasRole('mentor')) $routeName = 'dashboard.mentor';
    }
@endphp

<x-ui::button 
    label="{{ __('Dasbor') }}" 
    icon="tabler-home" 
    link="{{ route($routeName) }}" 
    class="btn-ghost btn-sm {{ request()->routeIs('dashboard.*') ? 'btn-active' : '' }}" 
/>
