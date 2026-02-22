<div>
    <x-ui::header
        :title="$form->id ? __('Edit User') : __('New User')"
        :subtitle="$form->id ? __('Update user details and roles.') : __('Create a new system user.')"
        :context="'admin::ui.dashboard.user_management'"
        separator
        progress-indicator
    />

    <x-ui::form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <x-ui::card shadow>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input
                            label="{{ __('Full Name') }}"
                            wire:model="name"
                            icon="tabler.user"
                            required
                        />
                        <x-ui::input
                            label="{{ __('Username') }}"
                            wire:model="username"
                            icon="tabler.at"
                            required
                        />
                        <x-ui::input
                            label="{{ __('Email Address') }}"
                            wire:model="email"
                            icon="tabler.mail"
                            type="email"
                            required
                        />
                        <x-ui::input
                            label="{{ __('Password') }}"
                            wire:model="password"
                            icon="tabler.key"
                            type="password"
                            hint="{{ $user ? __('Leave blank to keep current password.') : '' }}"
                        />

                        <x-ui::select
                            label="{{ __('Account Status') }}"
                            wire:model="status"
                            :options="[
                                ['id' => 'active', 'name' => __('Active')],
                                ['id' => 'pending', 'name' => __('Pending')],
                                ['id' => 'inactive', 'name' => __('Inactive')],
                            ]"
                            icon="tabler.palette"
                            required
                        />

                        @if (in_array('teacher', $selectedRoles))
                            <x-ui::input
                                label="{{ __('NIP') }}"
                                wire:model="nip"
                                icon="tabler.id"
                                hint="{{ __('Required for Teachers') }}"
                            />
                        @endif

                        @if (in_array('student', $selectedRoles))
                            <x-ui::input
                                label="{{ __('NISN') }}"
                                wire:model="nisn"
                                icon="tabler.school"
                                hint="{{ __('Required for Students') }}"
                            />
                        @endif
                    </div>

                    <div class="mt-6">
                        <x-ui::choices
                            label="{{ __('Assign Roles') }}"
                            wire:model="selectedRoles"
                            :options="$roles"
                            option-label="name"
                            option-value="name"
                            icon="tabler.shield-check"
                            hint="{{ __('Select one or more roles for this user.') }}"
                            compact
                        />
                    </div>
                </x-ui::card>
            </div>

            <div class="lg:col-span-1">
                <x-ui::card title="{{ __('Avatar') }}" shadow>
                    <x-ui::file wire:model="avatar" accept="image/*" crop-after-change>
                        <img
                            src="{{ $avatar?->temporaryUrl() ?? ($user?->avatar_url ?? '/avatar.png') }}"
                            class="h-40 rounded-lg"
                        />
                    </x-ui::file>
                    <div class="text-xs opacity-50 mt-2">
                        {{ __('Recommended: Square image, max 1MB.') }}
                    </div>
                </x-ui::card>
            </div>
        </div>

        <x-slot:actions>
            <x-ui::button label="{{ __('Cancel') }}" link="/users" class="btn-ghost" />
                            <x-ui::button
                            label="{{ __('Save User') }}"
                            type="submit"
                            icon="tabler.check"
                            class="btn-primary"
                            spinner="save"
                        />        </x-slot>
    </x-ui::form>
</div>
