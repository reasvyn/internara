<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\ActionException;
use App\Modules\Core\Exceptions\ActionFailedException;
use App\Modules\Core\Exceptions\AppException;
use App\Modules\Core\Exceptions\InfrastructureException;
use App\Modules\Core\Exceptions\ModuleException;
use App\Modules\Core\Exceptions\PresentationException;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Exceptions\UnauthorizedException;
use App\Modules\Core\Exceptions\ValidationFailedException;

describe('89SRA: exception hierarchy', function (): void {
    test('89SRA-FR-LOG-031: AppException is an abstract RuntimeException root', function (): void {
        expect((new ReflectionClass(AppException::class))->isAbstract())->toBeTrue();
        expect(is_subclass_of(AppException::class, RuntimeException::class))->toBeTrue();
    });

    test('89SRA-FR-LOG-032: ModuleException is an abstract RuntimeException root', function (): void {
        expect((new ReflectionClass(ModuleException::class))->isAbstract())->toBeTrue();
        expect(is_subclass_of(ModuleException::class, RuntimeException::class))->toBeTrue();
    });

    test('89SRA-FR-LOG-033: ModuleException is not an AppException — sibling trees', function (): void {
        $rejected = new RejectedException('Quota full');

        expect($rejected)->toBeInstanceOf(ModuleException::class);
        expect($rejected)->not->toBeInstanceOf(AppException::class);
        expect(new ActionFailedException('Boom'))->not->toBeInstanceOf(ModuleException::class);
    });

    test('QLHDO-FR-GLB-018: a ModuleException catch never swallows infrastructure failures', function (): void {
        $caught = null;

        try {
            throw new ActionFailedException('Disk gone');
        } catch (ModuleException $e) {
            $caught = 'module';
        } catch (AppException $e) {
            $caught = 'app';
        }

        expect($caught)->toBe('app');

        $caught = null;

        try {
            throw new RejectedException('Quota full');
        } catch (ModuleException $e) {
            $caught = 'module';
        } catch (AppException $e) {
            $caught = 'app';
        }

        expect($caught)->toBe('module');
    });

    test('89SRA-FR-LOG-034: context trait round-trips hint and context', function (): void {
        $e = (new RejectedException('Quota full'))
            ->withHint('Try another company')
            ->withContext(['company_id' => 'abc', 'password' => 'secret']);

        expect($e->getHint())->toBe('Try another company');
        expect($e->getContext())->toBe(['company_id' => 'abc', 'password' => 'secret']);
        expect($e->getSanitizedContext())->toBe(['company_id' => 'abc', 'password' => '***']);
        expect($e->isUserFacing())->toBeTrue();
        expect($e->shouldReport())->toBeTrue();
    });

    test('89SRA-FR-LOG-034: toCliOutput renders message, hint, and masked context', function (): void {
        $e = (new RejectedException('Quota full'))
            ->withHint('Try another company')
            ->withContext(['email' => 'student@example.com']);

        $out = $e->toCliOutput();

        expect($out)->toContain('Quota full');
        expect($out)->toContain('Hint: Try another company');
        expect($out)->toContain('st***@example.com');
        expect($out)->not->toContain('student@example.com');
    });

    test('89SRA-FR-LOG-035: every AppException leaf reports its own HTTP status', function (): void {
        expect((new ValidationFailedException)->statusCode())->toBe(422);
        expect((new UnauthorizedException)->statusCode())->toBe(403);
        expect((new ActionFailedException('x'))->statusCode())->toBe(500);
    });

    test('89SRA-FR-LOG-036: RejectedException is the 400 business-rule voice', function (): void {
        $e = new RejectedException('Placement quota is full');

        expect($e->getMessage())->toBe('Placement quota is full');
        expect($e->statusCode())->toBe(400);
        expect($e->isUserFacing())->toBeTrue();
    });

    test('89SRA-FR-LOG-037: ValidationFailedException carries the resolved 422 hint', function (): void {
        app()->setLocale('en');
        expect((new ValidationFailedException)->getHint())
            ->toBe('Please review the provided data and try again.');

        app()->setLocale('id');
        expect((new ValidationFailedException)->getHint())
            ->toBe('Periksa kembali data yang Anda masukkan dan coba lagi.');
    });

    test('89SRA-FR-LOG-038: UnauthorizedException carries the resolved 403 hint', function (): void {
        $e = new UnauthorizedException;

        expect($e)->toBeInstanceOf(PresentationException::class);
        expect($e->statusCode())->toBe(403);
    });

    test('89SRA-FR-LOG-038: the default hint resolves in the construction locale', function (): void {
        app()->setLocale('en');
        expect((new UnauthorizedException)->getHint())->toBe('You are not authorized to perform this action.');

        app()->setLocale('id');
        expect((new UnauthorizedException)->getHint())
            ->toBe('Anda tidak memiliki izin untuk melakukan aksi ini.');
    });

    test('89SRA-FR-LOG-039: infrastructure failures are never user-facing', function (): void {
        $e = new ActionFailedException('Mount lost');

        expect($e->statusCode())->toBe(500);
        expect($e->isUserFacing())->toBeFalse();
        expect($e)->toBeInstanceOf(InfrastructureException::class);
    });

    test('SE5Q9-FR-BASE-035: ActionFailedException is an InfrastructureException leaf', function (): void {
        expect((new ReflectionClass(ActionFailedException::class))->isAbstract())->toBeFalse();
        expect(is_subclass_of(ActionFailedException::class, AppException::class))->toBeTrue();
    });

    test('89SRA-FR-LOG-037: ActionException branch stays user-facing at 400', function (): void {
        expect((new ValidationFailedException)->isUserFacing())->toBeTrue();
        expect(new class('x') extends ActionException {})->statusCode()->toBe(400);
    });
});
