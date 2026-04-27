<div class="w-full">
    <x-ui::form class="flex w-full flex-col gap-12" wire:submit="register">
        <div class="max-w-2xl mx-auto w-full space-y-12">
            <!-- Section 1: Authority Identity -->
            <div class="space-y-8">
                <div class="space-y-2 border-b border-base-content/5 pb-4">
                    <h3 class="text-xl font-bold tracking-tight text-base-content">{{ __('auth::ui.register_super_admin.authority_title') }}</h3>
                    <p class="text-sm text-base-content/60">{{ __('auth::ui.register_super_admin.authority_desc') }}</p>
                </div>

                <div class="rounded-3xl bg-primary/5 p-8 border border-primary/10 flex flex-col items-center text-center space-y-4">
                    <div class="size-16 rounded-full bg-primary/10 flex items-center justify-center">
                        <x-ui::icon name="tabler.shield-lock" class="size-8 text-primary" />
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-primary">{{ __('auth::ui.register_super_admin.sovereign_label') }}</h4>
                        <p class="text-xs text-base-content/60 leading-relaxed max-w-sm mx-auto">
                            {{ __('auth::ui.register_super_admin.sovereign_help') }}
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <x-ui::input
                        wire:model="form.name"
                        icon="tabler.user-shield"
                        :label="__('auth::ui.register_super_admin.form.name')"
                        readonly
                        class="bg-base-200/50 cursor-not-allowed font-bold text-primary"
                    />
                </div>
            </div>

            <!-- Section 2: Credentials & Security -->
            <div class="space-y-8" x-data="{ 
                password: '', 
                get strength() {
                    if (!this.password) return 0;
                    let s = 0;
                    if (this.password.length >= 12) s += 25;
                    if (/[A-Z]/.test(this.password)) s += 25;
                    if (/[0-9]/.test(this.password)) s += 25;
                    if (/[^A-Za-z0-9]/.test(this.password)) s += 25;
                    return s;
                }
            }">
                <div class="space-y-2 border-b border-base-content/5 pb-4">
                    <h3 class="text-xl font-bold tracking-tight text-base-content">{{ __('auth::ui.register_super_admin.security_title') }}</h3>
                    <p class="text-sm text-base-content/60">{{ __('auth::ui.register_super_admin.security_desc') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <x-ui::input
                        wire:model="form.email"
                        type="email"
                        icon="tabler.mail"
                        :label="__('auth::ui.register_super_admin.form.email')"
                        :placeholder="__('auth::ui.register_super_admin.form.email_placeholder')"
                        required
                    />

                    <div class="space-y-4">
                        <x-ui::input
                            wire:model="form.password"
                            x-model="password"
                            type="password"
                            icon="tabler.lock"
                            :label="__('auth::ui.register_super_admin.form.password')"
                            :placeholder="__('auth::ui.register_super_admin.form.password_placeholder')"
                            required
                        />
                        
                        <!-- Password Strength Indicator -->
                        <div class="space-y-2" x-show="password">
                            <div class="flex justify-between items-center text-[10px] uppercase tracking-widest font-bold">
                                <span :class="{
                                    'text-error': strength <= 25,
                                    'text-warning': strength == 50,
                                    'text-info': strength == 75,
                                    'text-success': strength == 100
                                }">
                                    <span x-show="strength <= 25">{{ __('auth::ui.password_strength.weak') }}</span>
                                    <span x-show="strength == 50">{{ __('auth::ui.password_strength.fair') }}</span>
                                    <span x-show="strength == 75">{{ __('auth::ui.password_strength.strong') }}</span>
                                    <span x-show="strength == 100">{{ __('auth::ui.password_strength.excellent') }}</span>
                                </span>
                                <span class="text-base-content/40" x-text="strength + '%'"></span>
                            </div>
                            <div class="h-1.5 w-full bg-base-200 rounded-full overflow-hidden">
                                <div class="h-full transition-all duration-500" :style="'width: ' + strength + '%'" :class="{
                                    'bg-error': strength <= 25,
                                    'bg-warning': strength == 50,
                                    'bg-info': strength == 75,
                                    'bg-success': strength == 100
                                }"></div>
                            </div>
                        </div>
                        <p class="text-[10px] text-base-content/50 leading-relaxed italic">
                            {{ __('auth::ui.register_super_admin.form.password_hint') }}
                        </p>
                    </div>

                    <x-ui::input
                        wire:model="form.password_confirmation"
                        type="password"
                        icon="tabler.lock-check"
                        :label="__('auth::ui.register_super_admin.form.password_confirmation')"
                        :placeholder="__('auth::ui.register_super_admin.form.password_confirmation_placeholder')"
                        required
                    />
                </div>
            </div>
        </div>

        <!-- Global Action -->
        <div class="flex flex-col items-center pt-10 border-t border-base-content/5" wire:key="rsa-actions">
            <div class="w-full max-w-md space-y-4 text-center">
                <x-ui::button
                    variant="primary"
                    class="btn-lg w-full shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-95"
                    :label="__('auth::ui.register_super_admin.form.submit')"
                    type="submit"
                    spinner="register"
                />

                <div class="flex items-start gap-3 p-4 rounded-xl bg-warning/5 border border-warning/10 text-warning-content/80 text-start">
                    <x-ui::icon name="tabler.alert-triangle" class="size-5 shrink-0 mt-0.5" />
                    <p class="text-xs leading-relaxed">
                        {{ __('auth::ui.register_super_admin.form.footer_warning') }}
                    </p>
                </div>
            </div>
        </div>
    </x-ui::form>
</div>