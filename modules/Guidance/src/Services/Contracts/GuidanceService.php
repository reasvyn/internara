<?php

declare(strict_types=1);

namespace Modules\Guidance\Services\Contracts;

use Modules\Guidance\Models\GuidanceSession;

interface GuidanceService
{
    public function findById(string $id): ?GuidanceSession;
    public function create(array $data): GuidanceSession;
    public function update(GuidanceSession $session, array $data): void;
    public function delete(GuidanceSession $session): void;
    public function getStudentSessions(string $studentId): array;
}
