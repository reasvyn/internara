<div class="p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Academic Years</h1>
        <p class="text-base-content/70">Manage academic year periods</p>
    </div>

    <div class="bg-base-100 border border-base-200 rounded-lg shadow p-6">
        <table class="w-full">
            <thead>
                <tr class="border-b border-base-200">
                    <th class="text-left py-3 px-4">Name</th>
                    <th class="text-left py-3 px-4">Start</th>
                    <th class="text-left py-3 px-4">End</th>
                    <th class="text-left py-3 px-4">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($years as $year)
                    <tr class="border-b border-base-200">
                        <td class="py-3 px-4">{{ $year->name }}</td>
                        <td class="py-3 px-4">{{ $year->start_date->format('Y-m-d') }}</td>
                        <td class="py-3 px-4">{{ $year->end_date->format('Y-m-d') }}</td>
                        <td class="py-3 px-4">
                            @if($year->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-ghost">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-base-content/50">No academic years found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $years->links() }}
        </div>
    </div>
</div>
