<?php

declare(strict_types=1);

namespace App\Repositories\Internship;

use App\Models\Internship;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for complex Internship queries.
 * 
 * S3 - Scalable: Abstracts complex queries for reusability.
 * Only use when queries are complex or reused across multiple Actions.
 */
class InternshipRepository
{
    /**
     * Find internships available for a specific student.
     * Complex query with multiple conditions and relationships.
     */
    public function findAvailableForStudent(Student $student): Collection
    {
        return Internship::query()
            ->where('status', '=', 'open')
            ->whereDoesntHave('registrations', function ($query) use ($student) {
                $query->where('student_id', '=', $student->id);
            })
            ->with(['company', 'requirements'])
            ->orderBy('start_date', 'asc')
            ->get();
    }
    
    /**
     * Find internships by multiple filters.
     * Example of reusable complex query.
     */
    public function findByFilters(array $filters): Collection
    {
        $query = Internship::query();
        
        if (isset($filters['status'])) {
            $query->where('status', '=', $filters['status']);
        }
        
        if (isset($filters['department_id'])) {
            $query->where('department_id', '=', $filters['department_id']);
        }
        
        if (isset($filters['start_date_after'])) {
            $query->where('start_date', '>=', $filters['start_date_after']);
        }
        
        return $query->with(['company', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Find internship with all related data for detail view.
     * Demonstrates eager loading optimization.
     */
    public function findWithDetails(string $internshipId): ?Internship
    {
        return Internship::with([
            'company',
            'department',
            'requirements',
            'placements.student',
            'registrations.student',
        ])->find($internshipId);
    }
}
