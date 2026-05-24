<div class="py-4">
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('school.title') }}</h2>
        <p class="text-sm text-base-content/50 mt-1">{{ __('school.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-base-100 border border-base-content/10 rounded-xl">
                <div class="p-6 sm:p-8">
                    <form wire:submit="save" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <x-mary-input
                                    label="{{ __('school.name') }}"
                                    placeholder="{{ __('school.name_placeholder') }}"
                                    wire:model="form.name"
                                />
                            </div>

                            <x-mary-input
                                label="{{ __('school.institutional_code') }}"
                                placeholder="{{ __('school.institutional_code_placeholder') }}"
                                :hint="__('school.institutional_code_hint')"
                                wire:model="form.institutional_code"
                            />

                            <x-mary-input
                                label="{{ __('school.email') }}"
                                type="email"
                                placeholder="{{ __('school.email_placeholder') }}"
                                wire:model="form.email"
                            />

                            <x-mary-input
                                label="{{ __('school.phone') }}"
                                placeholder="{{ __('school.phone_placeholder') }}"
                                wire:model="form.phone"
                            />

                            <x-mary-input
                                label="{{ __('school.fax') }}"
                                placeholder="{{ __('school.fax_placeholder') }}"
                                wire:model="form.fax"
                            />

                            <div class="md:col-span-2">
                                <x-mary-textarea
                                    label="{{ __('school.address') }}"
                                    placeholder="{{ __('school.address_placeholder') }}"
                                    rows="3"
                                    wire:model="form.address"
                                />
                            </div>

                            <x-mary-input
                                label="{{ __('school.website') }}"
                                type="url"
                                placeholder="{{ __('school.website_placeholder') }}"
                                wire:model="form.website"
                            />

                            <x-mary-input
                                label="{{ __('school.principal_name') }}"
                                placeholder="{{ __('school.principal_name_placeholder') }}"
                                wire:model="form.principal_name"
                            />
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-base-content/10">
                            <x-mary-button
                                :label="__('school.discard')"
                                link="{{ url()->previous() }}"
                                class="btn-ghost btn-sm"
                            />
                            <x-mary-button
                                :label="__('school.save_changes')"
                                type="submit"
                                class="btn-primary btn-sm"
                                icon="o-check"
                                spinner="save"
                            />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- School Logo --}}
            <div class="bg-base-100 border border-base-content/10 rounded-xl p-6">
                <div class="flex flex-col items-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('school.logo') }}</p>
                    <div class="relative mb-2 group">
                        <div class="cursor-pointer relative" onclick="document.getElementById('school-logo-upload').click()">
                            <input id="school-logo-upload" type="file" wire:model="logo_file" accept="image/png,image/jpeg,image/webp" class="hidden" />
                            @if($this->logoPreviewUrl() ?? $school->getFirstMediaUrl('logo', 'thumb'))
                                <img src="{{ $this->logoPreviewUrl() ?? $school->getFirstMediaUrl('logo', 'thumb') }}"
                                     alt="School logo"
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
                    <p class="text-[10px] text-base-content/40 text-center">{{ __('school.logo_hint') }}</p>
                </div>
            </div>

            {{-- System Context --}}
            <div class="bg-base-200/40 border border-base-content/10 rounded-xl p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-4">{{ __('school.system_context') }}</p>
                <p class="text-sm text-base-content/60 leading-relaxed mb-4">
                    {{ __('school.system_context_desc') }}
                </p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-check-badge" class="size-4 text-success shrink-0" />
                        <span class="text-xs text-base-content/50">{{ __('school.uuid_enabled') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-shield-check" class="size-4 text-info shrink-0" />
                        <span class="text-xs text-base-content/50">{{ __('school.audit_logged') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
