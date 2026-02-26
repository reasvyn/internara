<div>
    <x-ui::header 
        :title="__('setting::ui.title')" 
        :subtitle="__('setting::ui.subtitle')"
        :context="'admin::ui.menu.group_system'"
    />

    <x-ui::form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                {{-- 1. General & Branding --}}
                <x-ui::card :title="__('setting::ui.groups.general')" shadow separator>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-ui::input 
                                :label="__('setting::ui.fields.brand_name')" 
                                icon="tabler.id-badge-2"
                                wire:model="brand_name" 
                                required 
                            />
                            <x-ui::select 
                                :label="__('setting::ui.fields.default_locale')" 
                                icon="tabler.language"
                                wire:model="default_locale" 
                                :options="[
                                    ['id' => 'id', 'name' => 'Bahasa Indonesia'],
                                    ['id' => 'en', 'name' => 'English'],
                                ]"
                                required 
                            />
                        </div>
                        <x-ui::input 
                            :label="__('setting::ui.fields.site_title')" 
                            icon="tabler.browser"
                            wire:model="site_title" 
                            required 
                        />
                    </div>
                </x-ui::card>

                {{-- 2. Operational Rules --}}
                <x-ui::card :title="__('setting::ui.groups.operational')" shadow separator>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-ui::input 
                            :label="__('setting::ui.fields.active_academic_year')" 
                            icon="tabler.calendar-bolt"
                            wire:model="active_academic_year" 
                            placeholder="e.g. 2025/2026"
                            required 
                        />
                        <x-ui::input 
                            :label="__('setting::ui.fields.attendance_check_in_start')" 
                            icon="tabler.clock-play"
                            type="time"
                            wire:model="attendance_check_in_start" 
                            required 
                        />
                        <x-ui::input 
                            :label="__('setting::ui.fields.attendance_late_threshold')" 
                            icon="tabler.clock-stop"
                            type="time"
                            wire:model="attendance_late_threshold" 
                            required 
                        />
                    </div>
                </x-ui::card>

                {{-- 3. Mail Services --}}
                <x-ui::card :title="__('setting::ui.groups.mail')" shadow separator>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ui::input 
                            :label="__('setting::ui.fields.mail_from_address')" 
                            icon="tabler.mail-forward"
                            type="email"
                            wire:model="mail_from_address" 
                        />
                        <x-ui::input 
                            :label="__('setting::ui.fields.mail_from_name')" 
                            icon="tabler.user-bolt"
                            wire:model="mail_from_name" 
                        />
                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-base-200 pt-6 mt-2">
                            <x-ui::input 
                                :label="__('setting::ui.fields.mail_host')" 
                                icon="tabler.server"
                                wire:model="mail_host" 
                                class="md:col-span-2"
                            />
                            <x-ui::input 
                                :label="__('setting::ui.fields.mail_port')" 
                                icon="tabler.external-link"
                                wire:model="mail_port" 
                            />
                            <x-ui::select 
                                :label="__('setting::ui.fields.mail_encryption')" 
                                icon="tabler.lock-cog"
                                wire:model="mail_encryption" 
                                :options="[
                                    ['id' => 'tls', 'name' => 'TLS'],
                                    ['id' => 'ssl', 'name' => 'SSL'],
                                    ['id' => 'none', 'name' => 'None'],
                                ]"
                            />
                            <x-ui::input 
                                :label="__('setting::ui.fields.mail_username')" 
                                icon="tabler.user"
                                wire:model="mail_username" 
                            />
                            <x-ui::input 
                                :label="__('setting::ui.fields.mail_password')" 
                                icon="tabler.key"
                                type="password"
                                wire:model="mail_password" 
                            />
                        </div>
                    </div>
                </x-ui::card>
            </div>

            <div class="lg:col-span-1 space-y-6">
                {{-- System Information (Read-only) --}}
                <x-ui::card :title="__('setting::ui.groups.system')" shadow separator class="bg-base-200/50">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-base-content/60">{{ __('setting::ui.fields.app_name') }}</span>
                            <span class="font-bold text-base-content">{{ $app_name }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-base-content/60">{{ __('setting::ui.fields.app_version') }}</span>
                            <x-ui::badge :value="$app_version" variant="neutral" outline size="badge-sm" />
                        </div>
                    </div>
                </x-ui::card>

                {{-- Visual Identity Assets --}}
                <x-ui::card :title="__('setting::ui.groups.identity')" shadow separator>
                    <div class="space-y-8">
                        <x-ui::file 
                            :label="__('setting::ui.fields.brand_logo')" 
                            wire:model="brand_logo" 
                            accept="image/*"
                            :preview="$current_logo_url"
                            hint="{{ __('setting::ui.hints.brand_logo') }}"
                        />

                        <x-ui::file 
                            :label="__('setting::ui.fields.site_favicon')" 
                            wire:model="site_favicon" 
                            accept="image/*"
                            :preview="$current_favicon_url"
                            hint="{{ __('setting::ui.hints.site_favicon') }}"
                        />
                    </div>
                </x-ui::card>
            </div>
        </div>

        <x-slot:actions>
            <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" icon="tabler.check" spinner="save" />
        </x-slot:actions>
    </x-ui::form>
</div>
