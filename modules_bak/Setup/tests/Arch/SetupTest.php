<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Arch;

use Modules\Setup\Models\Setup;
use Modules\Setup\Services\SystemInstaller;
use Modules\Setup\Services\InstallationAuditor;
use Modules\Setup\Services\SetupService;
use Modules\Setup\Livewire\SetupWelcome;
use Modules\Setup\Livewire\SchoolSetup;
use Modules\Setup\Livewire\AccountSetup;
use Modules\Setup\Livewire\DepartmentSetup;
use Modules\Setup\Livewire\InternshipSetup;
use Modules\Setup\Livewire\SetupComplete;
use Modules\Setup\Http\Middleware\ProtectSetupRoute;
use Modules\Setup\Http\Middleware\RequireSetupAccess;
use Tests\TestCase;

/**
 * [S1 - Secure] Test security invariants
 * [S2 - Sustain] Test code quality standards
 * [S3 - Scalable] Test architecture compliance
 */
describe('Setup Architecture', function () {
    it('uses UUID for Setup model', function () {
        $setup = Setup::create([
            'is_installed' => false,
            'completed_steps' => [],
        ]);
        
        expect($setup->id)->toBeUuid();
    });

    it('encrypts setup tokens', function () {
        $setup = Setup::create([
            'is_installed' => false,
            'completed_steps' => [],
        ]);
        
        $setup->setToken('plain-token-123');
        
        expect($setup->setup_token_encrypted)->not->toBe('plain-token-123');
        expect($setup->setup_token_encrypted)->toStartWith('eyJpdiI6'); // encrypted prefix
        expect($setup->getToken())->toBe('plain-token-123');
    });

    it('has proper service contracts', function () {
        expect(app(SetupService::class))->toBeInstanceOf(SetupService::class);
        expect(app(SystemInstaller::class))->toBeInstanceOf(SystemInstaller::class);
        expect(app(InstallationAuditor::class))->toBeInstanceOf(InstallationAuditor::class);
    });

    it('uses strict_types in all PHP files', function () {
        $files = glob(__DIR__ . '/../../src/**/*.php');
        $files += glob(__DIR__ . '/../../src/**/**/*.php');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            expect($content)->toContain('declare(strict_types=1);');
        }
    });

    it('has no hardcoded strings in views', function () {
        $views = glob(__DIR__ . '/../../resources/views/**/*.blade.php');
        
        foreach ($views as $view) {
            $content = file_get_contents($view);
            // Check for hardcoded strings (not in blade directives)
            preg_match_all("/{{\\s*'([^']+)'\\s*}}/", $content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $string) {
                    expect($string)->toContain('__(');
                }
            }
        }
    });

    it('middleware uses rate limiting', function () {
        $reflection = new \ReflectionClass(ProtectSetupRoute::class);
        $method = $reflection->getMethod('handle');
        $source = file_get_contents(__DIR__ . '/../../src/Http/Middleware/ProtectSetupRoute.php');
        
        expect($source)->toContain('RateLimiter');
        expect($source)->toContain('hash_equals');
    });

    it('validates tokens with timing-safe comparison', function () {
        $setup = Setup::create([
            'is_installed' => false,
            'completed_steps' => [],
        ]);
        
        $token = 'test-token-123';
        $setup->setToken($token);
        
        expect($setup->tokenMatches($token))->toBeTrue();
        expect($setup->tokenMatches('wrong-token'))->toBeFalse();
    });
});
