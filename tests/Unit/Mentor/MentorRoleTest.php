<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Mentor\Entities\MentorRole;

describe('MentorRole entity', function () {
    it('detects school teacher type', function () {
        $entity = new MentorRole(type: MentorRole::TYPE_SCHOOL_TEACHER);

        expect($entity->isSchoolTeacher())->toBeTrue()
            ->and($entity->isIndustrySupervisor())->toBeFalse();
    });

    it('detects industry supervisor type', function () {
        $entity = new MentorRole(type: MentorRole::TYPE_INDUSTRY_SUPERVISOR);

        expect($entity->isIndustrySupervisor())->toBeTrue()
            ->and($entity->isSchoolTeacher())->toBeFalse();
    });

    it('returns correct role enum for school teacher', function () {
        $entity = new MentorRole(type: MentorRole::TYPE_SCHOOL_TEACHER);

        expect($entity->role())->toBe(Role::TEACHER);
    });

    it('school teacher can verify everything', function () {
        $entity = new MentorRole(type: MentorRole::TYPE_SCHOOL_TEACHER);

        expect($entity->canVerifyAttendance())->toBeTrue()
            ->and($entity->canVerifyLogbook())->toBeTrue()
            ->and($entity->canVerifySupervisionLog())->toBeTrue()
            ->and($entity->canFinalizeAssessment())->toBeTrue();
    });

    it('industry supervisor cannot verify', function () {
        $entity = new MentorRole(type: MentorRole::TYPE_INDUSTRY_SUPERVISOR);

        expect($entity->canVerifyAttendance())->toBeFalse()
            ->and($entity->canVerifyLogbook())->toBeFalse()
            ->and($entity->canVerifySupervisionLog())->toBeFalse()
            ->and($entity->canFinalizeAssessment())->toBeFalse()
            ->and($entity->canCreateSupervisionLog())->toBeTrue();
    });
});
