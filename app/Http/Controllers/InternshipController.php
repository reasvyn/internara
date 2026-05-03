<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Internship\Actions\CreateInternshipAction;
use App\Domain\Internship\Actions\DeleteInternshipAction;
use App\Domain\Internship\Actions\UpdateInternshipAction;
use App\Domain\Internship\Models\Internship;
use App\Http\Requests\Internship\CreateInternshipRequest;
use App\Http\Requests\UpdateInternshipRequest;
use App\Repositories\Internship\InternshipRepository;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for Internship management.
 *
 * S2 - Sustain: Thin controller, delegates to Actions.
 * S1 - Secure: Uses Form Requests for validation.
 */
class InternshipController extends Controller
{
    public function __construct(
        private readonly CreateInternshipAction $createAction,
        private readonly UpdateInternshipAction $updateAction,
        private readonly DeleteInternshipAction $deleteAction,
        private readonly InternshipRepository $repository,
    ) {}

    /**
     * List all internships with optional filters.
     */
    public function index(): JsonResponse
    {
        $internships = $this->repository->findByFilters(request()->all());

        return response()->json([
            'data' => $internships,
        ]);
    }

    /**
     * Show a specific internship with details.
     */
    public function show(string $id): JsonResponse
    {
        $internship = $this->repository->findWithDetails($id);

        if (! $internship) {
            return response()->json(['message' => 'Internship not found'], 404);
        }

        return response()->json([
            'data' => $internship,
        ]);
    }

    /**
     * Create a new internship.
     */
    public function store(CreateInternshipRequest $request): JsonResponse
    {
        $internship = $this->createAction->execute($request);

        return response()->json(
            [
                'message' => 'Internship created successfully',
                'data' => $internship,
            ],
            201,
        );
    }

    /**
     * Update an existing internship.
     */
    public function update(UpdateInternshipRequest $request, Internship $internship): JsonResponse
    {
        $updated = $this->updateAction->execute($internship, $request);

        return response()->json([
            'message' => 'Internship updated successfully',
            'data' => $updated,
        ]);
    }

    /**
     * Delete an internship.
     */
    public function destroy(Internship $internship): JsonResponse
    {
        $this->deleteAction->execute($internship);

        return response()->json([
            'message' => 'Internship deleted successfully',
        ]);
    }
}
