<div class="p-8">
    <x-mary-header title="Supervision Logs" subtitle="Track your guidance and mentoring sessions" separator progress-indicator />

    @if(!$registration)
        <div class="alert alert-warning">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
            No active internship registration found.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 space-y-6">
                <x-mary-card shadow class="bg-base-100 border border-base-200">
                    @php
                        $headers = [
                            ['key' => 'date', 'label' => 'Date'],
                            ['key' => 'type', 'label' => 'Type'],
                            ['key' => 'topic', 'label' => 'Topic'],
                            ['key' => 'is_verified', 'label' => 'Status'],
                        ];
                    @endphp

                    <x-mary-table :headers="$headers" :rows="$logs" with-pagination>
                        @scope('cell_date', $log)
                            {{ $log->date->format('d M Y') }}
                        @endscope

                        @scope('cell_type', $log)
                            <x-mary-badge :value="ucfirst($log->type)" :class="$log->type === 'guidance' ? 'badge-primary' : 'badge-secondary'" />
                        @endscope

                        @scope('cell_is_verified', $log)
                            @if($log->is_verified)
                                <x-mary-badge value="Verified" class="badge-success" />
                            @else
                                <x-mary-badge value="Pending" class="badge-neutral" />
                            @endif
                        @endscope
                    </x-mary-table>
                </x-mary-card>
            </div>

            <div class="space-y-6">
                <x-mary-card title="Assigned Supervisors" shadow class="bg-base-200/50">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 p-2 rounded-lg">
                                <x-mary-icon name="o-user-group" class="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <div class="text-xs opacity-50 uppercase font-bold">Teacher (Supervisor)</div>
                                <div class="font-medium">{{ $registration->teacher?->name ?? 'Not Assigned' }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="bg-secondary/10 p-2 rounded-lg">
                                <x-mary-icon name="o-briefcase" class="w-5 h-5 text-secondary" />
                            </div>
                            <div>
                                <div class="text-xs opacity-50 uppercase font-bold">Industry Mentor</div>
                                <div class="font-medium">{{ $registration->mentor?->name ?? 'Not Assigned' }}</div>
                            </div>
                        </div>
                    </div>
                </x-mary-card>
            </div>
        </div>
    @endif
</div>
