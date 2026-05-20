<div class="py-4">
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('setting.title') }}</h2>
        <p class="text-sm text-base-content/50 mt-1">{{ __('setting.subtitle') }}</p>
    </div>

    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- General --}}
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title><span class="font-semibold">{{ __('setting.groups.general') }}</span></x-slot:title>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="{{ __('setting.fields.brand_name') }}" wire:model="brand_name" />
                        <x-mary-select
                            label="{{ __('setting.fields.default_locale') }}"
                            wire:model="default_locale"
                            :options="[
                                ['id' => 'id', 'name' => 'Bahasa Indonesia'],
                                ['id' => 'en', 'name' => 'English'],
                            ]"
                        />
                        <x-mary-input
                            label="{{ __('setting.fields.site_title') }}"
                            wire:model="site_title"
                            class="md:col-span-2"
                        />
                        <x-mary-input
                            label="{{ __('setting.fields.active_academic_year') }}"
                            wire:model="active_academic_year"
                            placeholder="2025/2026"
                        />
                    </div>
                </x-mary-card>

                {{-- Color Scheme --}}
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title><span class="font-semibold">{{ __('setting.groups.color_scheme') }}</span></x-slot:title>
                    <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('setting.hints.color_scheme') }}</span></x-slot:subtitle>

                    {{-- Presets --}}
                    <div class="mb-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setting.presets_title') }}</p>
                        <div class="flex flex-wrap gap-3">
                            @php $presets = App\Domain\Shared\Support\Theme::presets(); @endphp
                            @foreach($presets as $key => $preset)
                                <button type="button"
                                    wire:click="applyPreset('{{ $key }}')"
                                    @class([
                                        'relative flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all duration-200 cursor-pointer hover:scale-105',
                                        'border-primary shadow-md shadow-primary/10' => $selected_preset === $key,
                                        'border-base-content/10 hover:border-base-content/30' => $selected_preset !== $key,
                                    ])>
                                    {{-- Color swatches --}}
                                    <div class="flex -space-x-1.5">
                                        <span class="size-5 rounded-full ring-2 ring-base-100" style="background: {{ $preset['colors']['primary'] }}"></span>
                                        <span class="size-5 rounded-full ring-2 ring-base-100" style="background: {{ $preset['colors']['secondary'] }}"></span>
                                        <span class="size-5 rounded-full ring-2 ring-base-100" style="background: {{ $preset['colors']['accent'] }}"></span>
                                        <span class="size-5 rounded-full ring-2 ring-base-100" style="background: {{ $preset['colors']['base'] }}"></span>
                                    </div>
                                    <span class="text-xs font-medium whitespace-nowrap">{{ $preset['label'] }}</span>
                                    @if($selected_preset === $key)
                                        <span class="absolute -top-1.5 -right-1.5 size-4 bg-primary text-primary-content rounded-full flex items-center justify-center">
                                            <x-mary-icon name="o-check" class="size-2.5" />
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Manual pickers --}}
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setting.custom_title') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-mary-input
                            label="{{ __('setting.fields.primary_color') }}"
                            type="color"
                            wire:model.live="primary_color"
                            wire:change="$set('selected_preset', null)"
                        />
                        <x-mary-input
                            label="{{ __('setting.fields.secondary_color') }}"
                            type="color"
                            wire:model.live="secondary_color"
                            wire:change="$set('selected_preset', null)"
                        />
                        <x-mary-input
                            label="{{ __('setting.fields.accent_color') }}"
                            type="color"
                            wire:model.live="accent_color"
                            wire:change="$set('selected_preset', null)"
                        />
                        <x-mary-input
                            label="{{ __('setting.fields.base_color') }}"
                            type="color"
                            wire:model.live="base_color"
                        />
                    </div>
                </x-mary-card>

                {{-- Mail --}}
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title><span class="font-semibold">{{ __('setting.groups.mail') }}</span></x-slot:title>
                    <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('setting.hints.mail') }}</span></x-slot:subtitle>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="{{ __('setting.fields.mail_from_address') }}" type="email" wire:model="mail_from_address" />
                        <x-mary-input label="{{ __('setting.fields.mail_from_name') }}" wire:model="mail_from_name" />
                        <x-mary-input label="{{ __('setting.fields.mail_host') }}" wire:model="mail_host" />
                        <x-mary-input label="{{ __('setting.fields.mail_port') }}" wire:model="mail_port" />
                        <x-mary-select
                            label="{{ __('setting.fields.mail_encryption') }}"
                            wire:model="mail_encryption"
                            :options="[
                                ['id' => 'tls', 'name' => 'TLS'],
                                ['id' => 'ssl', 'name' => 'SSL'],
                                ['id' => 'none', 'name' => 'None'],
                            ]"
                        />
                        <x-mary-input label="{{ __('setting.fields.mail_username') }}" wire:model="mail_username" />
                        <x-mary-input label="{{ __('setting.fields.mail_password') }}" type="password" wire:model="mail_password" />
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-mary-button
                            label="{{ __('setting.buttons.test_mail') }}"
                            icon="o-paper-airplane"
                            class="btn-ghost btn-sm"
                            wire:click="testEmail"
                            spinner="testEmail"
                        />
                    </div>
                </x-mary-card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- System Info --}}
                <x-mary-card class="bg-base-200/30 border border-base-content/10">
                    <x-slot:title><span class="font-semibold">{{ __('setting.groups.system') }}</span></x-slot:title>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/50">{{ __('setting.fields.app_name') }}</span>
                            <span class="font-medium">{{ $app_name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/50">{{ __('setting.fields.app_version') }}</span>
                            <x-mary-badge :value="$app_version" class="badge-neutral badge-sm" />
                        </div>
                    </div>
                </x-mary-card>

                {{-- Identity Assets --}}
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title><span class="font-semibold">{{ __('setting.groups.identity') }}</span></x-slot:title>
                    <div class="flex flex-col items-center gap-6 pt-4">
                        {{-- Brand Logo --}}
                        <div class="flex flex-col items-center">
                            <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setting.fields.brand_logo') }}</p>
                            <div class="relative group">
                                <div class="cursor-pointer relative" onclick="document.getElementById('brand-logo-upload').click()">
                                    <input id="brand-logo-upload" type="file" wire:model="brand_logo" accept="image/png,image/jpeg,image/webp" class="hidden" />
                                    @if($this->brandLogoPreviewUrl() ?? $current_logo_url)
                                        <img src="{{ $this->brandLogoPreviewUrl() ?? $current_logo_url }}"
                                             alt="Brand logo"
                                             class="size-24 rounded-xl object-contain border border-base-content/10" />
                                    @else
                                        <div class="size-24 rounded-xl bg-base-200 flex items-center justify-center border border-dashed border-base-content/20">
                                            <x-mary-icon name="o-building-office" class="size-8 text-base-content/30" />
                                        </div>
                                    @endif
                                    <div class="absolute inset-0 flex items-center justify-center rounded-xl bg-base-content/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <x-mary-icon name="o-camera" class="size-8 text-base-100" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Favicon --}}
                        <div class="flex flex-col items-center">
                            <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setting.fields.site_favicon') }}</p>
                            <div class="relative group">
                                <div class="cursor-pointer relative" onclick="document.getElementById('favicon-upload').click()">
                                    <input id="favicon-upload" type="file" wire:model="site_favicon" accept="image/png,image/jpeg,image/x-icon" class="hidden" />
                                    @if($this->faviconPreviewUrl() ?? $current_favicon_url)
                                        <img src="{{ $this->faviconPreviewUrl() ?? $current_favicon_url }}"
                                             alt="Favicon"
                                             class="size-12 rounded-lg object-contain border border-base-content/10" />
                                    @else
                                        <div class="size-12 rounded-lg bg-base-200 flex items-center justify-center border border-dashed border-base-content/20">
                                            <x-mary-icon name="o-globe-alt" class="size-5 text-base-content/30" />
                                        </div>
                                    @endif
                                    <div class="absolute inset-0 flex items-center justify-center rounded-lg bg-base-content/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <x-mary-icon name="o-camera" class="size-5 text-base-100" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-mary-card>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('setting.buttons.save')" type="submit" class="btn-primary" icon="o-check" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
