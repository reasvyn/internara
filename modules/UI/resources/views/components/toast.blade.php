<div 
    x-data="{
        toasts: [],
        translations: {
            success: '{{ __('ui::common.success') }}',
            error: '{{ __('ui::common.error') }}',
            warning: '{{ __('ui::common.warning') }}',
            info: '{{ __('ui::common.notification') }}'
        },
        add(payload) {
            if (!payload) return;

            // Handle array of notifications
            if (Array.isArray(payload)) {
                payload.forEach(item => this.add(item));
                return;
            }

            const message = typeof payload === 'string' ? payload : (payload.message || payload.description);
            if (!message) return;

            const type = payload.type || 'info';
            
            // Prevent duplicates in current active/visible queue
            if (this.toasts.some(t => t.message === message && t.type === type && t.visible)) {
                return;
            }

            const id = Math.random().toString(36).substring(2, 9) + Date.now();
            const timeout = payload.timeout || 5000;
            const autohide = payload.autohide !== undefined ? payload.autohide : true;

            const toast = {
                id,
                message,
                type,
                title: payload.title || this.translations[type],
                visible: false,
                progress: 100,
                autohide
            };

            this.toasts.push(toast);

            // Trigger enter animation with a small delay to ensure DOM is ready
            setTimeout(() => {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.toasts[index].visible = true;
                }
            }, 50);

            // Progress bar animation
            if (autohide) {
                const start = Date.now();
                const timer = setInterval(() => {
                    const index = this.toasts.findIndex(t => t.id === id);
                    if (index === -1) {
                        clearInterval(timer);
                        return;
                    }
                    const elapsed = Date.now() - start;
                    this.toasts[index].progress = Math.max(0, 100 - (elapsed / timeout * 100));
                    
                    if (this.toasts[index].progress <= 0) {
                        clearInterval(timer);
                    }
                }, 50);

                setTimeout(() => {
                    this.remove(id);
                }, timeout);
            } else {
                // Keep progress bar full for non-autohide
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) this.toasts[index].progress = 100;
            }
        },
        remove(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                this.toasts[index].visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 400);
            }
        }
    }"
    @notify.window="add($event.detail)"
    class="toast toast-end toast-bottom z-[9999] gap-3 p-6 pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="translate-y-8 opacity-0 scale-90"
            x-transition:enter-end="translate-y-0 opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="alert relative overflow-hidden shadow-2xl border border-base-content/5 flex items-start gap-4 min-w-[22rem] max-w-md pointer-events-auto cursor-pointer transition-all hover:scale-[1.02] active:scale-[0.98] rounded-2xl p-4 bg-base-100/60 backdrop-blur-xl text-base-content"
            role="status"
            aria-live="polite"
            @click="remove(toast.id)"
        >
            <div 
                class="mt-0.5 p-2 rounded-xl"
                :class="{
                    'bg-success/10 text-success': toast.type === 'success',
                    'bg-error/10 text-error': toast.type === 'error',
                    'bg-warning/10 text-warning': toast.type === 'warning',
                    'bg-info/10 text-info': toast.type === 'info',
                }"
            >
                <template x-if="toast.type === 'success'"><x-ui::icon name="tabler.check" class="size-5" /></template>
                <template x-if="toast.type === 'error'"><x-ui::icon name="tabler.alert-circle" class="size-5" /></template>
                <template x-if="toast.type === 'warning'"><x-ui::icon name="tabler.alert-triangle" class="size-5" /></template>
                <template x-if="toast.type === 'info'"><x-ui::icon name="tabler.info-circle" class="size-5" /></template>
            </div>
            
            <div class="flex-1 pt-1">
                <h4 class="font-black text-sm uppercase tracking-widest opacity-70" x-text="toast.title"></h4>
                <p class="text-xs font-bold leading-relaxed opacity-100 mt-0.5" x-text="toast.message"></p>
            </div>

            <button 
                class="btn btn-ghost btn-circle btn-xs -mr-1 opacity-40 hover:opacity-100" 
                @click.stop="remove(toast.id)"
                aria-label="{{ __('ui::common.close') }}"
            >
                <x-ui::icon name="tabler.x" class="size-3" />
            </button>

            {{-- Time Bar (Progress) --}}
            <div 
                class="absolute bottom-0 left-0 h-1 transition-all duration-75 ease-linear" 
                :class="{
                    'bg-success': toast.type === 'success',
                    'bg-error': toast.type === 'error',
                    'bg-warning': toast.type === 'warning',
                    'bg-info': toast.type === 'info',
                }"
                :style="`width: ${toast.progress}%`"
            ></div>
        </div>
    </template>
</div>
