<x-ui::record-manager>
    <x-slot:filters>
        <x-ui::dropdown :close-on-content-click="false" right>
            <x-slot:trigger>
                <x-ui::button icon="tabler.filter" variant="secondary" class="gap-2">
                    <span>{{ __('user::ui.manager.filters.open') }}</span>
                    @if($this->activeFilterCount() > 0)
                        <x-ui::badge :value="$this->activeFilterCount()" variant="info" class="badge-sm" />
                    @endif
                </x-ui::button>
            </x-slot:trigger>

            <div class="w-[min(92vw,30rem)] space-y-4 p-2">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @if(!$targetRole)
                        <x-ui::select
                            :label="__('user::ui.manager.filters.role')"
                            icon="tabler.shield"
                            wire:model.live="filters.role"
                            :options="[
                                ['id' => 'student', 'name' => __('permission::roles.student')],
                                ['id' => 'teacher', 'name' => __('permission::roles.teacher')],
                                ['id' => 'mentor', 'name' => __('permission::roles.mentor')],
                            ]"
                            :placeholder="__('user::ui.manager.filters.all_roles')"
                        />
                    @endif

                    <x-ui::select
                        :label="__('user::ui.manager.filters.status')"
                        icon="tabler.circle-check"
                        wire:model.live="filters.status"
                        :options="[
                            ['id' => 'active', 'name' => __('user::ui.manager.form.active')],
                            ['id' => 'pending', 'name' => __('user::ui.manager.form.pending')],
                            ['id' => 'inactive', 'name' => __('user::ui.manager.form.inactive')],
                        ]"
                        :placeholder="__('user::ui.manager.filters.all_statuses')"
                    />

                    <x-ui::input
                        :label="__('user::ui.manager.filters.created_from')"
                        icon="tabler.calendar-down"
                        type="date"
                        wire:model.live="filters.created_from"
                    />

                    <x-ui::input
                        :label="__('user::ui.manager.filters.created_to')"
                        icon="tabler.calendar-up"
                        type="date"
                        wire:model.live="filters.created_to"
                    />
                </div>

                <div class="flex justify-end">
                    <x-ui::button
                        :label="__('user::ui.manager.filters.reset')"
                        icon="tabler.filter-off"
                        variant="secondary"
                        wire:click="resetFilters"
                    />
                </div>
            </div>
        </x-ui::dropdown>
    </x-slot:filters>

    <x-slot:rowActions></x-slot:rowActions>

    {{-- Form Fields --}}
    <x-slot:formFields>
        <div
            class="space-y-4"
            x-data="{
                roles: $wire.entangle('form.roles').live,
                status: $wire.entangle('form.status').live,
                hasRole(role) {
                    return Array.isArray(this.roles) && this.roles.includes(role);
                },
                get isStudentContext() {
                    return this.hasRole('student');
                },
                get isTeacherContext() {
                    return !this.isStudentContext && this.hasRole('teacher');
                },
                get showsAcademicFields() {
                    return this.isStudentContext || this.isTeacherContext;
                },
                get isPrivilegedContext() {
                    return this.hasRole('admin') || this.hasRole('super-admin');
                }
            }"
            x-init="$watch('roles', (roles) => { if (Array.isArray(roles) && (roles.includes('admin') || roles.includes('super-admin'))) { status = 'verified'; } })"
        >
            <x-ui::input :label="__('user::ui.manager.form.full_name')" icon="tabler.signature" wire:model="form.name" required />

            <x-ui::input :label="__('user::ui.manager.form.email')" icon="tabler.mail" type="email" wire:model="form.email" required />

            @if($form->id)
                <x-ui::input :label="__('user::ui.manager.form.username')" icon="tabler.at" wire:model="form.username" readonly />
            @endif

            <x-ui::alert type="info" icon="tabler.lock">
                {{ $form->id ? __('user::ui.manager.form.password_reset_notice') : __('user::ui.manager.form.password_setup_notice') }}
            </x-ui::alert>

            @if(!$targetRole)
                <x-ui::choices
                    :label="__('user::ui.manager.form.roles')"
                    icon="tabler.shield-check"
                    wire:model="form.roles"
                    :options="[
                        ['id' => 'student', 'name' => __('permission::roles.student')],
                        ['id' => 'teacher', 'name' => __('permission::roles.teacher')],
                        ['id' => 'mentor', 'name' => __('permission::roles.mentor')],
                    ]"
                />
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="isStudentContext" x-cloak>
                <x-ui::input :label="__('user::ui.manager.form.nisn')" icon="tabler.id" wire:model="form.profile.national_identifier" placeholder="e.g. NISN" />
                <x-ui::input :label="__('user::ui.manager.form.nis')" icon="tabler.id-badge-2" wire:model="form.profile.registration_number" placeholder="e.g. NIS" />
            </div>

            <div x-show="isTeacherContext" x-cloak>
                <x-ui::input :label="__('user::ui.manager.form.nip')" icon="tabler.id-badge-2" wire:model="form.profile.registration_number" placeholder="e.g. NIP" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="showsAcademicFields" x-cloak>
                <x-ui::select 
                    :label="__('user::ui.manager.form.department')" 
                    icon="tabler.school"
                    wire:model="form.profile.department_id" 
                    :options="$this->departments" 
                    :placeholder="__('user::ui.manager.form.select_department')"
                />
                <x-ui::input :label="__('user::ui.manager.form.phone')" icon="tabler.phone" wire:model="form.profile.phone" />
            </div>

            <div x-show="showsAcademicFields" x-cloak>
                <x-ui::textarea :label="__('user::ui.manager.form.address')" icon="tabler.map-pin" wire:model="form.profile.address" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="showsAcademicFields" x-cloak>
                <x-ui::select 
                    :label="__('user::ui.manager.form.gender')" 
                    icon="tabler.gender-intersex"
                    wire:model="form.profile.gender" 
                    :options="[
                        ['id' => 'male', 'name' => __('profile::enums.gender.male')],
                        ['id' => 'female', 'name' => __('profile::enums.gender.female')],
                    ]" 
                    :placeholder="__('user::ui.manager.form.select_gender')"
                />
                <x-ui::select 
                    :label="__('user::ui.manager.form.blood_type')" 
                    icon="tabler.droplet"
                    wire:model="form.profile.blood_type" 
                    :options="[
                        ['id' => 'A', 'name' => 'A'],
                        ['id' => 'B', 'name' => 'B'],
                        ['id' => 'AB', 'name' => 'AB'],
                        ['id' => 'O', 'name' => 'O'],
                    ]" 
                    :placeholder="__('user::ui.manager.form.select_blood_type')"
                />
            </div>

            <x-ui::select 
                :label="__('user::ui.manager.form.status')" 
                icon="tabler.circle-check"
                wire:model="form.status" 
                ::disabled="isPrivilegedContext"
                :options="[
                    ['id' => 'active', 'name' => __('user::ui.manager.form.active')],
                    ['id' => 'inactive', 'name' => __('user::ui.manager.form.inactive')],
                    ['id' => 'pending', 'name' => __('user::ui.manager.form.pending')],
                ]" 
            />
        </div>
    </x-slot:formFields>
</x-ui::record-manager>
