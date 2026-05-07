<div class="p-8">
    <x-mary-header title="Clone Detection" subtitle="Identify potential duplicate or cloned accounts" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Back to Lifecycle" icon="o-arrow-left" class="btn-ghost" href="{{ route('admin.accounts.lifecycle') }}" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        @if ($clones->isEmpty())
            <div class="text-center py-8">
                <x-mary-icon name="o-shield-check" class="w-12 h-12 mx-auto mb-3 text-success" />
                <p class="text-lg font-medium">No duplicate accounts detected.</p>
                <p class="text-sm opacity-60">All user accounts have unique email addresses.</p>
            </div>
        @else
            <div class="alert alert-warning mb-6">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <div>
                    <h3 class="font-bold">{{ $clones->count() }} potential duplicate(s) found</h3>
                    <p class="text-sm">These accounts share the same email address and may require review.</p>
                </div>
            </div>

            @php
                $headers = [
                    ['key' => 'type', 'label' => 'Type'],
                    ['key' => 'identifier', 'label' => 'Identifier'],
                    ['key' => 'accounts', 'label' => 'Accounts'],
                ];
            @endphp

            <x-mary-table :headers="$headers" :rows="$clones">
                @scope('cell_type', $clone)
                    <x-mary-badge value="Duplicate Email" class="badge-warning" />
                @endscope

                @scope('cell_identifier', $clone)
                    <span class="font-mono text-sm">{{ $clone['identifier'] }}</span>
                @endscope

                @scope('cell_accounts', $clone)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($clone['user_ids'] as $userId)
                            @php
                                $user = \App\Models\User::find($userId);
                            @endphp
                            @if ($user)
                                <a href="{{ route('admin.users.students') }}" class="badge badge-ghost badge-sm">
                                    {{ $user->name }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endscope
            </x-mary-table>
        @endif
    </x-mary-card>
</div>
