<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\ActionFailedException;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Exceptions\UnauthorizedException;
use App\Modules\Core\Exceptions\ValidationFailedException;

describe('89SRA: exception reporting defaults and structural invariants', function (): void {
    test('89SRA-NFR-LOG-008: every exception volunteers for reporting by default', function (): void {
        expect((new RejectedException('Quota full'))->shouldReport())->toBeTrue()
            ->and((new ActionFailedException('Disk gone'))->shouldReport())->toBeTrue();
    });

    test('89SRA-NFR-LOG-003: infrastructure failures are never user-facing', function (): void {
        expect((new ActionFailedException('Mount lost'))->isUserFacing())->toBeFalse();
    });

    test('89SRA-NFR-LOG-009: CLI output keeps full context with secrets masked', function (): void {
        $original = new RuntimeException('storage mount gone');
        $e = new ActionFailedException('Certificate worker failed', 0, $original);
        $e->withHint('Check the disk mount')->withContext([
            'password' => 'S3cret-pw-99',
            'token' => 'tok_live_abc123',
            'worker' => 'certificates',
        ]);

        $out = $e->toCliOutput();

        expect($out)->toContain('Certificate worker failed')
            ->and($out)->toContain('Hint: Check the disk mount')
            ->and($out)->toContain('worker: certificates')
            ->and($out)->toContain('Previous: storage mount gone')
            ->and($out)->not->toContain('S3cret-pw-99')
            ->and($out)->not->toContain('tok_live_abc123');
    });

    test('89SRA-NFR-LOG-010: exception hierarchy stays flat at three levels or fewer', function (): void {
        $files = glob(app_path('Modules/Core/Exceptions/*.php'));
        $depths = [];

        foreach ($files as $file) {
            $class = 'App\\Modules\\Core\\Exceptions\\'.basename($file, '.php');
            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $depth = 1;
            $parent = $reflection->getParentClass();
            while ($parent instanceof ReflectionClass && $parent->getName() !== RuntimeException::class) {
                $depth++;
                $parent = $parent->getParentClass();
            }

            $depths[$class] = $depth;
        }

        expect($depths)->not->toBeEmpty()
            ->and(count($depths))->toBeGreaterThanOrEqual(3)
            ->and(max($depths))->toBeLessThanOrEqual(3);
    });

    test('89SRA-NFR-LOG-011: every exception file holds exactly its own class', function (): void {
        $files = glob(app_path('Modules/Core/Exceptions/*.php'));

        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $expected = basename($file, '.php');
            $code = file_get_contents($file);
            preg_match_all('/^\s*(?:final\s+|abstract\s+)?class\s+(\w+)/m', $code, $matches);

            expect($matches[1])->toEqual([$expected]);
        }
    });

    test('89SRA-NFR-LOG-012: user-facing error strings resolve in both locales', function (): void {
        app()->setLocale('en');
        $enValidation = (new ValidationFailedException)->getHint();
        $enUnauthorized = (new UnauthorizedException)->getHint();
        $enUnexpected = __('exceptions.unexpected');
        $enActionFailed = __('core.errors.action_failed_hint');

        app()->setLocale('id');
        $idValidation = (new ValidationFailedException)->getHint();
        $idUnauthorized = (new UnauthorizedException)->getHint();
        $idUnexpected = __('exceptions.unexpected');
        $idActionFailed = __('core.errors.action_failed_hint');

        expect($enValidation)->toBe('Please review the provided data and try again.')
            ->and($idValidation)->toBe('Periksa kembali data yang Anda masukkan dan coba lagi.')
            ->and($enUnauthorized)->toBe('You are not authorized to perform this action.')
            ->and($idUnauthorized)->toBe('Anda tidak memiliki izin untuk melakukan aksi ini.')
            ->and($enUnexpected)->toBe('An unexpected error occurred.')
            ->and($idUnexpected)->toBe('Kesalahan tidak diketahui.')
            ->and($enActionFailed)->toBe('An unexpected error occurred while performing this action.')
            ->and($idActionFailed)->toBe('Terjadi kesalahan tak terduga saat melakukan aksi ini.');
    });
});
