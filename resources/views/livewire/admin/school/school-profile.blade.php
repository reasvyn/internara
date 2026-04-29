<div class="p-8">
    <x-mary-header title="School Profile" subtitle="Manage institution identity and contact information" separator progress-indicator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <x-mary-card shadow class="bg-base-100 border border-base-200">
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-mary-input label="School Name" wire:model="name" icon="o-academic-cap" />
                        <x-mary-input label="Institutional Code" wire:model="institutional_code" icon="o-hashtag" />
                        
                        <div class="md:col-span-2">
                            <x-mary-textarea label="Address" wire:model="address" rows="3" icon="o-map-pin" />
                        </div>

                        <x-mary-input label="Principal Name" wire:model="principal_name" icon="o-user" />
                        <x-mary-input label="Official Email" type="email" wire:model="email" icon="o-envelope" />
                        <x-mary-input label="Phone Number" wire:model="phone" icon="o-phone" />
                    </div>

                    <div class="flex justify-end gap-2 border-t border-base-200 pt-6">
                        <x-mary-button label="Discard" link="{{ route('setup') }}" />
                        <x-mary-button label="Save Changes" type="submit" class="btn-primary" spinner="save" />
                    </div>
                </form>
            </x-mary-card>
        </div>

        <div>
            <x-mary-card title="System Context" shadow class="bg-base-200/50">
                <p class="text-sm text-base-content/70">
                    This information is used for generating certificates, formal letters, and internship reports.
                </p>
                <div class="mt-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 p-2 rounded-lg">
                            <x-mary-icon name="o-check-badge" class="w-5 h-5 text-primary" />
                        </div>
                        <span class="text-sm font-medium">UUID Enabled</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 p-2 rounded-lg">
                            <x-mary-icon name="o-shield-check" class="w-5 h-5 text-primary" />
                        </div>
                        <span class="text-sm font-medium">Audit-Logged</span>
                    </div>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
