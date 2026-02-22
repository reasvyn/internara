<div>
    <x-ui::header 
        :title="__('setting::ui.title')" 
        :subtitle="__('setting::ui.subtitle')"
        :context="'admin::ui.menu.group_system'"
    />

    <x-ui::form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <x-ui::card :title="__('setting::ui.groups.general')" shadow separator>
                    <div class="grid grid-cols-1 gap-4">
                        <x-ui::input 
                            :label="__('setting::ui.fields.brand_name')" 
                            icon="tabler.id-badge-2"
                            wire:model="brand_name" 
                            required 
                        />
                        <x-ui::input 
                            :label="__('setting::ui.fields.site_title')" 
                            icon="tabler.browser"
                            wire:model="site_title" 
                            required 
                        />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-ui::input 
                                :label="__('setting::ui.fields.app_version')" 
                                icon="tabler.versions"
                                wire:model="app_version" 
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
                    </div>
                </x-ui::card>
            </div>

            <div class="lg:col-span-1 space-y-6">
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
