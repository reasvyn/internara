<div class="p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Handbooks</h1>
        <p class="text-base-content/70">Manage guidance documents and student handbooks</p>
    </div>

    <div class="bg-base-100 border border-base-200 rounded-lg shadow p-6">
        <table class="w-full">
            <thead>
                <tr class="border-b border-base-200">
                    <th class="text-left py-3 px-4">Title</th>
                    <th class="text-left py-3 px-4">Version</th>
                    <th class="text-left py-3 px-4">Status</th>
                    <th class="text-left py-3 px-4">Published</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handbooks as $handbook)
                    <tr class="border-b border-base-200">
                        <td class="py-3 px-4">{{ $handbook->title }}</td>
                        <td class="py-3 px-4">{{ $handbook->version }}</td>
                        <td class="py-3 px-4">
                            @if($handbook->is_active)
                                <span class="badge badge-success">Published</span>
                            @else
                                <span class="badge badge-ghost">Draft</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">{{ $handbook->published_at?->format('Y-m-d') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-base-content/50">No handbooks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $handbooks->links() }}
        </div>
    </div>
</div>
