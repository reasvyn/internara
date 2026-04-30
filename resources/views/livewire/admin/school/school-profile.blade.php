<div class="p-8">
    <x-mary-header :title="__('school.title')" :subtitle="__('school.subtitle')" separator progress-indicator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <x-mary-card shadow class="bg-base-100 border border-base-200">
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-mary-input :label="__('school.name')" wire:model="name" :placeholder="__('school.name_placeholder')" icon="o-academic-cap" />
                        <x-mary-input :label="__('school.institutional_code')" wire:model="institutional_code" :placeholder="__('school.institutional_code_placeholder')" icon="o-hashtag" :hint="__('school.institutional_code_hint')" />

                        <div class="md:col-span-2">
                            <x-mary-textarea :label="__('school.address')" wire:model="address" :placeholder="__('school.address_placeholder')" rows="3" icon="o-map-pin" />
                        </div>

                        <x-mary-input :label="__('school.principal_name')" wire:model="principal_name" :placeholder="__('school.principal_name_placeholder')" icon="o-user" />
                        <x-mary-input :label="__('school.email')" type="email" wire:model="email" :placeholder="__('school.email_placeholder')" icon="o-envelope" />
                        <x-mary-input :label="__('school.phone')" wire:model="phone" :placeholder="__('school.phone_placeholder')" icon="o-phone" />
                        <x-mary-input :label="__('school.fax')" wire:model="fax" :placeholder="__('school.fax_placeholder')" icon="o-printer" />
                    </div>

                    <div class="flex justify-end gap-2 border-t border-base-200 pt-6">
                        <x-mary-button :label="__('school.discard')" link="{{ url()->previous() }}" />
                        <x-mary-button :label="__('school.save_changes')" type="submit" class="btn-primary" spinner="save" />
                    </div>
                </form>
            </x-mary-card>
        </div>

        <div>
            <x-mary-card :title="__('school.system_context')" shadow class="bg-base-200/50">
                <p class="text-sm text-base-content/70">
                    {{ __('school.system_context_desc') }}
                </p>
                <div class="mt-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 p-2 rounded-lg">
                            <x-mary-icon name="o-check-badge" class="w-5 h-5 text-primary" />
                        </div>
                        <span class="text-sm font-medium">{{ __('school.uuid_enabled') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 p-2 rounded-lg">
                            <x-mary-icon name="o-shield-check" class="w-5 h-5 text-primary" />
                        </div>
                        <span class="text-sm font-medium">{{ __('school.audit_logged') }}</span>
                    </div>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
