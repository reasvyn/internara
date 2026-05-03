<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Guidance\Actions\AcknowledgeHandbookAction;
use App\Domain\Guidance\Actions\CreateHandbookAction;
use App\Domain\Guidance\Models\Handbook;
use App\Http\Requests\CreateHandbookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HandbookController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Handbook::class);

        $handbooks = Handbook::with('author')->latest()->paginate(20);

        return view('livewire.admin.handbooks.index', [
            'handbooks' => $handbooks,
        ]);
    }

    public function store(CreateHandbookRequest $request, CreateHandbookAction $action)
    {
        Gate::authorize('create', Handbook::class);

        $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('admin.handbooks.index')
            ->with('success', 'Handbook created successfully.');
    }

    public function acknowledge(
        Handbook $handbook,
        Request $request,
        AcknowledgeHandbookAction $action,
    ) {
        $action->execute($request->user(), $handbook);

        return back()->with('success', 'Handbook acknowledged.');
    }
}
