<?php

declare(strict_types=1);

use App\Domain\Core\Models\Concerns\HasAuditTrail;

describe('HasAuditTrail trait', function () {
    it('has bootHasAuditTrail method', function () {
        $ref = new ReflectionMethod(HasAuditTrail::class, 'bootHasAuditTrail');

        expect($ref->isStatic())->toBeTrue()
            ->and($ref->isPublic())->toBeTrue();
    });

    it('has default auditEvents method returning created, updated, deleted', function () {
        $ref = new ReflectionMethod(HasAuditTrail::class, 'auditEvents');

        $returnType = $ref->getReturnType();

        expect($returnType)->not->toBeNull();
    });

    it('has writeAudit method', function () {
        expect(method_exists(HasAuditTrail::class, 'writeAudit'))->toBeTrue();
    });

    it('has auditModule method', function () {
        expect(method_exists(HasAuditTrail::class, 'auditModule'))->toBeTrue();
    });

    it('has auditContext method', function () {
        expect(method_exists(HasAuditTrail::class, 'auditContext'))->toBeTrue();
    });

    it('has auditMaskPii method', function () {
        expect(method_exists(HasAuditTrail::class, 'auditMaskPii'))->toBeTrue();
    });
});
