@props(['items' => []])
@php
$currentRoute = request()->route()?->getName() ?? '';
$brandName = brand('name');
$brandLogo = brand('logo');
@endphp

<div class="drawer-side z-[60]">
    <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
    
    <aside class="bg-base-100 min-h-screen w-[280px] border-r border-base-content/5 flex flex-col shadow-2xl lg:shadow-none transition-all">
        
        <!-- Logo Header -->
        <div class="h-20 px-8 border-b border-base-content/5 flex items-center shrink-0">
            <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center gap-3 group w-full">
                <div class="size-10 rounded-2xl bg-primary flex items-center justify-center shadow-lg shadow-primary/20 transition-all group-hover:scale-105 duration-500 shrink-0">
                    <img src="{{ $brandLogo }}" class="size-6 object-contain brightness-0 invert" alt="{{ $brandName }}" />
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-black text-lg truncate leading-none tracking-tighter text-base-content">{{ $brandName }}</span>
                    <span class="text-[8px] uppercase tracking-[0.3em] font-black opacity-30 mt-1.5">Management</span>
                </div>
            </a>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto px-4 py-8 space-y-10 scrollbar-hide">
            
            @auth
                {{-- Role-based dynamic groups --}}
                @php
                    $navGroups = [
                        'administration' => [
                            'roles' => ['super_admin', 'admin'],
                            'title' => __('admin.title'),
                            'items' => [
                                ['route' => 'admin.school', 'icon' => 'o-academic-cap', 'label' => __('school.title')],
                                ['route' => 'admin.departments', 'icon' => 'o-rectangle-group', 'label' => __('department.title')],
                                ['route' => 'admin.internships', 'icon' => 'o-briefcase', 'label' => __('internship.title')],
                                ['route' => 'admin.companies', 'icon' => 'o-building-office', 'label' => __('company.title')],
                            ]
                        ],
                        'users' => [
                            'roles' => ['super_admin', 'admin'],
                            'title' => __('user.student.title'),
                            'items' => [
                                ['route' => 'admin.users.admins', 'icon' => 'o-user-circle', 'label' => __('user.admin.title')],
                                ['route' => 'admin.users.students', 'icon' => 'o-user-group', 'label' => __('user.student.title')],
                                ['route' => 'admin.users.teachers', 'icon' => 'o-academic-cap', 'label' => __('user.teacher.title')],
                                ['route' => 'admin.users.mentors', 'icon' => 'o-user-plus', 'label' => __('user.mentor.title')],
                            ]
                        ],
                        'portal' => [
                            'roles' => ['student'],
                            'title' => 'Student Portal',
                            'items' => [
                                ['route' => 'student.dashboard', 'icon' => 'o-home', 'label' => __('dashboard.title')],
                                ['route' => 'student.journals', 'icon' => 'o-book-open', 'label' => 'Journals'],
                            ]
                        ],
                        'supervision' => [
                            'roles' => ['teacher', 'supervisor'],
                            'title' => 'Supervision',
                            'items' => [
                                ['route' => 'supervision.logs', 'icon' => 'o-clipboard-check', 'label' => 'Guidance Logs'],
                                ['route' => 'supervision.monitoring', 'icon' => 'o-map-pin', 'label' => 'Monitoring'],
                            ]
                        ],
                        'system' => [
                            'roles' => ['super_admin', 'admin'],
                            'title' => 'System Settings',
                            'items' => [
                                ['route' => 'admin.settings', 'icon' => 'o-cog-6-tooth', 'label' => __('setting.title')],
                            ]
                        ]
                    ];
                @endphp

                @foreach($navGroups as $group)
                    @if(auth()->user()->hasRole($group['roles']))
                        <div class="space-y-4">
                            <h3 class="px-5 text-[10px] font-black uppercase tracking-[0.2em] text-base-content/20 italic">{{ $group['title'] }}</h3>
                            <ul class="space-y-1.5">
                                @foreach($group['items'] as $item)
                                    @php $active = request()->routeIs($item['route'] . '*'); @endphp
                                    <li>
                                        <a wire:navigate href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}" 
                                           @class([
                                               'flex items-center gap-3 px-5 py-3.5 rounded-2xl transition-all duration-300 group/nav',
                                               'bg-primary/10 text-primary shadow-sm ring-1 ring-primary/5' => $active,
                                               'text-base-content/50 hover:bg-base-200 hover:text-base-content' => !$active
                                           ])>
                                            <x-mary-icon :name="$item['icon']" @class([
                                                'size-5 transition-transform group-hover/nav:scale-110',
                                                'text-primary' => $active,
                                                'opacity-40' => !$active
                                            ]) />
                                            <span @class(['text-xs tracking-tight', 'font-black' => $active, 'font-bold' => !$active])>
                                                {{ $item['label'] }}
                                            </span>
                                            @if($active)
                                                <div class="ml-auto size-1.5 rounded-full bg-primary shadow-lg shadow-primary/50"></div>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            @else
                {{-- Setup Mode Placeholder --}}
                <div class="px-6 py-10 text-center bg-base-200/50 rounded-[2.5rem] border border-dashed border-base-content/10">
                    <div class="size-16 rounded-full bg-base-100 flex items-center justify-center mx-auto mb-4 shadow-xl">
                        <x-mary-icon name="o-shield-check" class="size-8 text-primary/40 animate-pulse" />
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30">System Provisioning</p>
                </div>
            @endauth
        </nav>

        <!-- Sidebar Bottom Actions -->
        <div class="p-6 border-t border-base-content/5 bg-base-200/20">
             <livewire:core.app-signature />
        </div>
    </aside>
</div>
