<div class="p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Schedules</h1>
        <p class="text-base-content/70">Manage internship program schedules and events</p>
    </div>

    <div class="bg-base-100 border border-base-200 rounded-lg shadow p-6">
        <table class="w-full">
            <thead>
                <tr class="border-b border-base-200">
                    <th class="text-left py-3 px-4">Title</th>
                    <th class="text-left py-3 px-4">Type</th>
                    <th class="text-left py-3 px-4">Start</th>
                    <th class="text-left py-3 px-4">End</th>
                    <th class="text-left py-3 px-4">Location</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                    <tr class="border-b border-base-200">
                        <td class="py-3 px-4">{{ $schedule->title }}</td>
                        <td class="py-3 px-4">
                            <span class="badge badge-neutral">{{ ucfirst($schedule->type) }}</span>
                        </td>
                        <td class="py-3 px-4">{{ $schedule->start_at->format('Y-m-d H:i') }}</td>
                        <td class="py-3 px-4">{{ $schedule->end_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="py-3 px-4">{{ $schedule->location ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-base-content/50">No schedules found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $schedules->links() }}
        </div>
    </div>
</div>
