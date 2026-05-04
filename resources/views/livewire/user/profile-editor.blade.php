<div>
    <x-mary-header title="{{ __('profile.title') }}" subtitle="{{ __('profile.subtitle') }}" separator progress-indicator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Profile Section --}}
        <div class="lg:col-span-2 space-y-8">
            <x-mary-card title="{{ __('profile.information') }}" subtitle="{{ __('profile.information_desc') }}" shadow class="rounded-[2rem] border-base-200">
                <x-mary-form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-mary-input label="{{ __('setup.wizard.full_name') }}" wire:model="data.name" icon="o-user" class="rounded-xl border-base-300" />
                        <x-mary-input label="{{ __('setup.wizard.email_address') }}" wire:model="data.email" icon="o-envelope" class="rounded-xl border-base-300" />
                        
                        <div class="md:col-span-2">
                            <x-mary-input label="{{ __('setup.wizard.school_phone') }}" wire:model="data.phone" icon="o-phone" class="rounded-xl border-base-300" />
                        </div>

                        <x-mary-textarea label="Bio" wire:model="data.bio" placeholder="Tell us about yourself..." class="md:col-span-2 rounded-xl border-base-300" rows="3" />
                        <x-mary-textarea label="{{ __('setup.wizard.school_address') }}" wire:model="data.address" class="md:col-span-2 rounded-xl border-base-300" rows="2" />
                    </div>
                    
                    <x-slot:actions>
                        <x-mary-button label="{{ __('profile.save_profile') }}" type="submit" icon="o-check" class="btn-primary rounded-xl font-bold uppercase tracking-widest" spinner="save" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-card>

            <x-mary-card title="{{ __('profile.password') }}" subtitle="{{ __('profile.password_desc') }}" shadow class="rounded-[2rem] border-base-200">
                <x-mary-form wire:submit="updatePassword">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-mary-password label="{{ __('profile.current_password') }}" wire:model="passwordData.current_password" class="rounded-xl border-base-300" />
                        <div class="hidden md:block"></div>
                        <x-mary-password label="{{ __('profile.new_password') }}" wire:model="passwordData.password" class="rounded-xl border-base-300" />
                        <x-mary-password label="{{ __('profile.confirm_password') }}" wire:model="passwordData.password_confirmation" class="rounded-xl border-base-300" />
                    </div>

                    <x-slot:actions>
                        <x-mary-button label="{{ __('profile.update_password') }}" type="submit" icon="o-key" class="btn-primary rounded-xl font-bold uppercase tracking-widest" spinner="updatePassword" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-card>
        </div>

        {{-- Sidebar / Preview --}}
        <div class="space-y-8">
            <x-mary-card class="rounded-[2rem] border-base-200 overflow-hidden shadow-sm">
                <div class="flex flex-col items-center text-center p-4">
                    <div class="relative group">
                        <x-mary-avatar :image="$user->avatar_url" class="!w-32 !h-32 rounded-3xl ring-4 ring-primary/10 mb-6 transition-transform group-hover:scale-105" />
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-mary-button icon="o-camera" class="btn-circle btn-primary btn-sm" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-black tracking-tight">{{ $user->name }}</h3>
                    <p class="text-sm text-base-content/50 font-mono mt-1">{{ $user->username }}</p>
                    
                    <div class="flex flex-wrap justify-center gap-1 mt-4">
                        @foreach($user->roles as $role)
                            <div class="badge badge-primary badge-outline font-black uppercase text-[10px]">{{ $role->name }}</div>
                        @endforeach
                    </div>
                </div>
                
                <div class="border-t border-base-200 mt-6 p-6 space-y-4">
                    <div class="flex items-center gap-3 text-sm">
                        <x-mary-icon name="o-envelope" class="size-4 text-base-content/40" />
                        <span class="text-base-content/70">{{ $user->email }}</span>
                    </div>
                    @if($user->profile?->phone)
                        <div class="flex items-center gap-3 text-sm">
                            <x-mary-icon name="o-phone" class="size-4 text-base-content/40" />
                            <span class="text-base-content/70">{{ $user->profile->phone }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-3 text-sm">
                        <x-mary-icon name="o-calendar" class="size-4 text-base-content/40" />
                        <span class="text-base-content/70 italic text-xs">Joined {{ $user->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </x-mary-card>

            <div class="p-8 bg-warning/5 border border-warning/20 rounded-[2rem] text-warning">
                <div class="flex items-start gap-4">
                    <x-mary-icon name="o-exclamation-triangle" class="size-6 shrink-0" />
                    <div>
                        <h4 class="font-bold text-sm mb-1">Critical Account Access</h4>
                        <p class="text-xs opacity-70 leading-relaxed">
                            Your account has administrative privileges. Ensure your password is secure and do not share your credentials.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
