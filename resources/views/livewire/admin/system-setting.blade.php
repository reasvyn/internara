<div class="p-8">
    <x-mary-header title="{{ __('setting.title') }}" subtitle="{{ __('setting.subtitle') }}" separator progress-indicator />

    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                {{-- General & Branding --}}
                <x-mary-card title="{{ __('setting.groups.general') }}" shadow separator class="bg-base-100 border border-base-200">
                    <div class="grid grid-cols-1 gap-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-mary-input label="{{ __('setting.fields.brand_name') }}" icon="o-identification" wire:model="brand_name" />
                            <x-mary-select
                                label="{{ __('setting.fields.default_locale') }}"
                                icon="o-language"
                                wire:model="default_locale"
                                :options="[
                                    ['id' => 'id', 'name' => 'Bahasa Indonesia'],
                                    ['id' => 'en', 'name' => 'English'],
                                ]"
                            />
                        </div>
                        <x-mary-input label="{{ __('setting.fields.site_title') }}" icon="o-globe-alt" wire:model="site_title" />
                    </div>
                </x-mary-card>

                {{-- Operational Rules --}}
                <x-mary-card title="{{ __('setting.groups.operational') }}" shadow separator class="bg-base-100 border border-base-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-mary-input
                            label="{{ __('setting.fields.active_academic_year') }}"
                            icon="o-calendar"
                            wire:model="active_academic_year"
                            placeholder="e.g. 2025/2026"
                        />
                        <x-mary-input
                            label="{{ __('setting.fields.attendance_check_in_start') }}"
                            icon="o-clock"
                            type="time"
                            wire:model="attendance_check_in_start"
                        />
                        <x-mary-input
                            label="{{ __('setting.fields.attendance_late_threshold') }}"
                            icon="o-clock"
                            type="time"
                            wire:model="attendance_late_threshold"
                        />
                    </div>
                </x-mary-card>

                {{-- Mail Services --}}
                <x-mary-card title="{{ __('setting.groups.mail') }}" shadow separator class="bg-base-100 border border-base-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-mary-input label="{{ __('setting.fields.mail_from_address') }}" icon="o-envelope" type="email" wire:model="mail_from_address" />
                        <x-mary-input label="{{ __('setting.fields.mail_from_name') }}" icon="o-user" wire:model="mail_from_name" />

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-base-200 pt-6 mt-2">
                            <x-mary-input label="{{ __('setting.fields.mail_host') }}" icon="o-server" wire:model="mail_host" class="md:col-span-2" />
                            <x-mary-input label="{{ __('setting.fields.mail_port') }}" icon="o-arrow-right-end-on-rectangle" wire:model="mail_port" />

                            <x-mary-select
                                label="{{ __('setting.fields.mail_encryption') }}"
                                icon="o-lock-closed"
                                wire:model="mail_encryption"
                                :options="[
                                    ['id' => 'tls', 'name' => 'TLS'],
                                    ['id' => 'ssl', 'name' => 'SSL'],
                                    ['id' => 'none', 'name' => 'None'],
                                ]"
                            />
                            <x-mary-input label="{{ __('setting.fields.mail_username') }}" icon="o-user" wire:model="mail_username" />
                            <x-mary-input label="{{ __('setting.fields.mail_password') }}" icon="o-key" type="password" wire:model="mail_password" />
                        </div>
                    </div>
                </x-mary-card>
            </div>

            <div class="lg:col-span-1 space-y-6">
                {{-- System Information (Read-only) --}}
                <x-mary-card title="{{ __('setting.groups.system') }}" shadow separator class="bg-base-200/50">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-base-content/60">{{ __('setting.fields.app_name') }}</span>
                            <span class="font-bold text-base-content">{{ $app_name }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-base-content/60">{{ __('setting.fields.app_version') }}</span>
                            <x-mary-badge value="{{ $app_version }}" class="badge-neutral" />
                        </div>
                    </div>
                </x-mary-card>

                {{-- Visual Identity Assets --}}
                <x-mary-card title="{{ __('setting.groups.identity') }}" shadow separator class="bg-base-100 border border-base-200">
                    <div class="space-y-8">
                        <x-mary-file
                            label="{{ __('setting.fields.brand_logo') }}"
                            wire:model="brand_logo"
                            accept="image/*"
                            :preview="$current_logo_url"
                            hint="{{ __('setting.hints.brand_logo') }}"
                        />

                        <x-mary-file
                            label="{{ __('setting.fields.site_favicon') }}"
                            wire:model="site_favicon"
                            accept="image/*"
                            :preview="$current_favicon_url"
                            hint="{{ __('setting.hints.site_favicon') }}"
                        />
                    </div>
                </x-mary-card>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Save Changes" type="submit" class="btn-primary" icon="o-check" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
