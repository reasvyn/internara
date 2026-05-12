@props(['items' => []])

<div class="drawer-side z-[60]">
    <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

    <aside class="bg-base-100 min-h-screen w-64 border-r border-base-content/10 flex flex-col">
        <div class="h-16 px-6 border-b border-base-content/10 flex items-center shrink-0">
            <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <x-ui::brand size="md" :with-tagline="false" :invert="false" />
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-6">
            @auth
                {{-- Dashboard --}}
                <ul class="space-y-0.5">
                    <li>
                        @php $active = request()->routeIs('dashboard'); @endphp
                        <a wire:navigate href="{{ route('dashboard') }}"
                           @class([
                               'flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors',
                               'bg-primary/10 text-primary font-medium' => $active,
                               'text-base-content/60 hover:bg-base-200 hover:text-base-content' => !$active,
                           ])>
                            <x-mary-icon name="o-home" class="size-4 shrink-0" />
                            <span>{{ __('dashboard.title') }}</span>
                        </a>
                    </li>
                </ul>

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
                            ],
                        ],
                        'users' => [
                            'roles' => ['super_admin', 'admin'],
                            'title' => __('user.student.title'),
                            'items' => [
                                ['route' => 'admin.users.admins', 'icon' => 'o-user-circle', 'label' => __('user.admin.title')],
                                ['route' => 'admin.users.students', 'icon' => 'o-user-group', 'label' => __('user.student.title')],
                                ['route' => 'admin.users.teachers', 'icon' => 'o-academic-cap', 'label' => __('user.teacher.title')],
                                ['route' => 'admin.users.mentors', 'icon' => 'o-user-plus', 'label' => __('user.mentor.title')],
                            ],
                        ],
                        'portal' => [
                            'roles' => ['student'],
                            'title' => 'Student Portal',
                            'items' => [
                                ['route' => 'student.dashboard', 'icon' => 'o-home', 'label' => __('dashboard.title')],
                                ['route' => 'student.logbook', 'icon' => 'o-book-open', 'label' => 'Journals'],
                            ],
                        ],
                        'supervision' => [
                            'roles' => ['teacher', 'supervisor'],
                            'title' => 'Supervision',
                            'items' => [
                                ['route' => 'supervision.logs', 'icon' => 'o-clipboard-check', 'label' => 'Guidance Logs'],
                            ],
                        ],
                        'system' => [
                            'roles' => ['super_admin', 'admin'],
                            'title' => 'System',
                            'items' => [
                                ['route' => 'admin.settings', 'icon' => 'o-cog-6-tooth', 'label' => __('setting.title')],
                            ],
                        ],
                    ];
                @endphp

                @foreach($navGroups as $group)
                    @if(auth()->user()->hasRole($group['roles']))
                        <div>
                            <h3 class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-base-content/30">{{ $group['title'] }}</h3>
                            <ul class="space-y-0.5">
                                @foreach($group['items'] as $item)
                                    @php $active = request()->routeIs($item['route'] . '*'); @endphp
                                    <li>
                                        <a wire:navigate href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                                           @class([
                                               'flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors',
                                               'bg-primary/10 text-primary font-medium' => $active,
                                               'text-base-content/60 hover:bg-base-200 hover:text-base-content' => !$active,
                                           ])>
                                            <x-mary-icon :name="$item['icon']" class="size-4 shrink-0" />
                                            <span>{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            @endauth
        </nav>
    </aside>
</div>
