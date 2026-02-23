<x-ui::record-manager>
    {{-- Custom Table Cells --}}
    <x-slot:tableCells>
        @scope('cell_name', $user)
            <div class="flex items-center gap-3">
                <x-ui::avatar :image="$user->avatar_url" :title="$user->name" size="w-8" />
                <div class="font-semibold">{{ $user->name }}</div>
            </div>
        @endscope

        @scope('cell_roles', $user)
            <div class="flex flex-wrap gap-1">
                @foreach($user->roles as $role)
                    <livewire:permission::role-badge :role="$role" size="xs" wire:key="role-{{ $user->id }}-{{ $role->id }}" />
                @endforeach
            </div>
        @endscope

        @scope('cell_account_status', $user)
            @php
                $statusName = $user->latestStatus()?->name ?? 'active';
            @endphp
            <x-ui::badge 
                :value="__('user::ui.manager.form.' . $statusName)" 
                :variant="$statusName === 'active' ? 'primary' : 'secondary'" 
                class="badge-sm" 
            />
        @endscope
    </x-slot:tableCells>

    {{-- Row Actions Override (for super-admin safety) --}}
    <x-slot:rowActions>
        @scope('actions', $user)
            <div class="flex justify-end gap-2">
                @if(!$user->hasRole('super-admin'))
                    <x-ui::button icon="tabler.edit" variant="tertiary" wire:click="edit('{{ $user->id }}')" class="text-info btn-xs" tooltip="{{ __('user::ui.manager.edit_' . $roleKey) }}" />
                    <x-ui::button 
                        icon="tabler.trash" 
                        variant="tertiary" 
                        wire:click="discard('{{ $user->id }}')" 
                        wire:confirm="{{ __('ui::common.delete_confirm') }}"
                        class="text-error btn-xs" 
                        tooltip="{{ __('ui::common.delete') }}" 
                    />
                @else
                    <x-ui::badge :value="__('System Protected')" variant="secondary" class="badge-sm opacity-50" />
                @endif
            </div>
        @endscope
    </x-slot:rowActions>

    {{-- Form Fields --}}
    <x-slot:formFields>
        <x-ui::input :label="__('user::ui.manager.form.full_name')" icon="tabler.signature" wire:model="form.name" required />
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui::input :label="__('user::ui.manager.form.email')" icon="tabler.mail" type="email" wire:model="form.email" required />
            @if($form->id)
                <x-ui::input :label="__('user::ui.manager.form.username')" icon="tabler.at" wire:model="form.username" readonly />
            @endif
        </div>

        <x-ui::input 
            :label="__('user::ui.manager.form.password')" 
            icon="tabler.key"
            type="password" 
            wire:model="form.password" 
            :placeholder="$form->id ? __('user::ui.manager.form.password_hint') : ''" 
        />

        {{-- Role-Specific Profile Fields --}}
        @if($targetRole === 'student')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input :label="__('user::ui.manager.form.national_identifier')" icon="tabler.id" wire:model="form.profile.national_identifier" placeholder="e.g. NISN" />
                <x-ui::input :label="__('user::ui.manager.form.registration_number')" icon="tabler.id-badge-2" wire:model="form.profile.registration_number" placeholder="e.g. NIS" />
            </div>
        @elseif($targetRole === 'teacher')
            <x-ui::input :label="__('user::ui.manager.form.registration_number')" icon="tabler.id-badge-2" wire:model="form.profile.registration_number" placeholder="e.g. NIP" />
        @endif

        @if(in_array($targetRole, ['student', 'teacher']))
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::select 
                    :label="__('user::ui.manager.form.department')" 
                    icon="tabler.school"
                    wire:model="form.profile.department_id" 
                    :options="$this->departments" 
                    :placeholder="__('user::ui.manager.form.select_department')"
                />
                <x-ui::input :label="__('user::ui.manager.form.phone')" icon="tabler.phone" wire:model="form.profile.phone" />
            </div>

            <x-ui::textarea :label="__('user::ui.manager.form.address')" icon="tabler.map-pin" wire:model="form.profile.address" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
        @endif

        @if(!$targetRole)
            <x-ui::choices
                :label="__('user::ui.manager.form.roles')"
                icon="tabler.shield-check"
                wire:model="form.roles"
                :options="[
                    ['id' => 'student', 'name' => __('permission::roles.student')],
                    ['id' => 'teacher', 'name' => __('permission::roles.teacher')],
                    ['id' => 'mentor', 'name' => __('permission::roles.mentor')],
                    ['id' => 'admin', 'name' => __('permission::roles.admin')],
                ]"
            />
        @endif

        <x-ui::select 
            :label="__('user::ui.manager.form.status')" 
            icon="tabler.circle-check"
            wire:model="form.status" 
            :options="[
                ['id' => 'active', 'name' => __('user::ui.manager.form.active')],
                ['id' => 'inactive', 'name' => __('user::ui.manager.form.inactive')],
            ]" 
        />
    </x-slot:formFields>
</x-ui::record-manager>