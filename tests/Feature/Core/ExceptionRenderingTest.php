<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\ActionFailedException;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Exceptions\UnauthorizedException;
use App\Modules\Setup\Domain\Installation\Http\Middleware\RequireSetupAccessMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

describe('89SRA: exception rendering', function (): void {
    test('89SRA-FR-LOG-047 + 89SRA-FR-LOG-048: business rejections render a 400 JSON envelope', function (): void {
        Route::get('/_89sra_rejected', function (): never {
            throw new RejectedException('Placement quota is full');
        });

        $response = $this->getJson('/_89sra_rejected');

        $response->assertStatus(400)->assertExactJson(['message' => 'Placement quota is full']);
    });

    test('89SRA-FR-LOG-046 + 89SRA-NFR-LOG-002: infrastructure internals never reach users', function (): void {
        Route::get('/_89sra_infra', function (): never {
            throw new ActionFailedException('SQLSTATE[HY000] [2002] Connection refused in /var/www/html/app/Models/Enrollment.php on line 42 #0 trace frame');
        });
        Route::get('/_89sra_forbidden', function (): never {
            throw new UnauthorizedException;
        });

        $infra = $this->getJson('/_89sra_infra');
        $forbidden = $this->getJson('/_89sra_forbidden');

        $infra->assertStatus(500)->assertExactJson(['message' => __('exceptions.unexpected')]);
        expect($infra->getContent())
            ->not->toContain('SQLSTATE')
            ->and($infra->getContent())->not->toContain('/var/www/html')
            ->and($infra->getContent())->not->toContain('#0');

        $forbidden->assertStatus(403)->assertExactJson(['message' => 'Unauthorized']);
    });

    test('89SRA-FR-LOG-049: browsers get the 500 view while other statuses abort', function (): void {
        Route::get('/_89sra_crash', function (): never {
            throw new ActionFailedException('Storage mount lost');
        });
        Route::get('/_89sra_rule', function (): never {
            throw new RejectedException('Placement quota is full');
        });

        // Pin the locale via cookie (first link in the Locale::current() chain)
        // so the assertion is deterministic regardless of DB setting state.
        $crash = $this->withCookie('locale', 'id')->get('/_89sra_crash');
        $rule = $this->withCookie('locale', 'id')->get('/_89sra_rule');

        $crash->assertStatus(500);
        expect($crash->getContent())->toContain('Kesalahan Server')
            ->and($crash->getContent())->toContain('favicon');

        $rule->assertStatus(400);
        // The 400 page is the branded 400 view (not the 500 view): code 400,
        // the business message stays visible, no 500 marker.
        expect($rule->getContent())->toContain('Placement quota is full')
            ->and($rule->getContent())->not->toContain('Kesalahan Server');
    });

    test('89SRA-FR-LOG-050: passwords never linger in flashed session input', function (): void {
        Route::post('/_89sra_flash_probe', function (): never {
            throw ValidationException::withMessages(['email' => 'The email address is invalid.']);
        })->middleware('web');

        $this->withoutMiddleware([RequireSetupAccessMiddleware::class]);

        $response = $this->from('/login')->post('/_89sra_flash_probe', [
            'email' => 'student@smkn2.sch.id',
            'password' => 'secret-pw',
            'password_confirmation' => 'secret-pw',
            'current_password' => 'old-secret',
        ]);

        $response->assertRedirect('/login');

        $old = session('_old_input', []);

        expect($old)->toMatchArray(['email' => 'student@smkn2.sch.id']);
        $this->assertArrayNotHasKey('password', $old);
        $this->assertArrayNotHasKey('password_confirmation', $old);
        $this->assertArrayNotHasKey('current_password', $old);
    });
});
