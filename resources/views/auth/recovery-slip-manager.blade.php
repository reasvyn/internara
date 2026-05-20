<div class="p-8">
    <x-mary-header title="Recovery Slip" subtitle="Generate a one-time credential slip for offline delivery" separator progress-indicator />

    <div class="max-w-2xl mx-auto">
        <x-mary-card shadow class="card-enterprise">
            @if($generatedCode)
                <div class="text-center space-y-6">
                    <div class="size-20 rounded-3xl bg-success/10 text-success flex items-center justify-center mx-auto">
                        <x-mary-icon name="o-document-text" class="size-10" />
                    </div>
                    <div>
                        <h3 class="text-xl font-black tracking-tight">Credential Slip Generated</h3>
                        <p class="text-sm text-base-content/60 mt-2">Deliver this code to the user offline. Expires: {{ $expiresAt }}</p>
                    </div>
                    <div class="bg-base-200 rounded-2xl p-6">
                        <p class="text-3xl font-mono font-black tracking-[0.3em] select-all">{{ $generatedCode }}</p>
                    </div>
                    <div class="bg-warning/5 border border-warning/20 rounded-2xl p-4 text-left">
                        <p class="text-xs font-bold uppercase tracking-widest text-warning">Security Note</p>
                        <p class="text-xs text-base-content/60 mt-1">This code will not be shown again. It expires in 24 hours. Verify the recipient's identity before delivery.</p>
                    </div>
                    <x-mary-button label="Generate Another" icon="o-plus" class="btn-primary" wire:click="resetForm" />
                </div>
            @else
                <div class="space-y-6">
                    <x-mary-input wire:model.live.debounce="search" label="Search User" placeholder="Name, username, or email..." icon="o-magnifying-glass" class="rounded-2xl" />

                    @if($search)
                        <div class="space-y-2">
                            @forelse($users as $user)
                                <div wire:key="user-{{ $user->id }}" 
                                     wire:click="selectUser('{{ $user->id }}')"
                                     class="flex items-center gap-4 p-4 rounded-2xl border transition-all cursor-pointer {{ $selectedUser?->id === $user->id ? 'border-primary bg-primary/5' : 'border-base-content/5 hover:bg-base-200' }}">
                                    <x-mary-avatar :title="$user->name" class="size-10 rounded-xl" />
                                    <div>
                                        <p class="font-bold text-sm">{{ $user->name }}</p>
                                        <p class="text-xs text-base-content/50">{{ $user->username }} @if($user->email) &middot; {{ $user->email }} @endif</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-base-content/40 text-center py-8">No users found.</p>
                            @endforelse
                        </div>
                    @endif

                    @if($selectedUser)
                        <div class="bg-primary/5 border border-primary/20 rounded-2xl p-4">
                            <p class="text-sm font-bold">Selected: {{ $selectedUser->name }} ({{ $selectedUser->username }})</p>
                        </div>

                        <div class="pt-4 border-t border-base-content/5">
                            <x-mary-button label="Generate Recovery Slip" icon="o-document-plus" class="btn-primary w-full" wire:click="generate" spinner="generate" />
                        </div>
                    @endif
                </div>
            @endif
        </x-mary-card>

        <div class="mt-6 text-center">
            <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold uppercase tracking-widest text-base-content/40 hover:text-primary" wire:navigate>Back to Dashboard</a>
        </div>
    </div>
</div>
