<?php

declare(strict_types=1);

namespace Tests\Unit\SysAdmin\Observability\Services;

use App\Core\Data\AuditReport;
use App\Core\Enums\AuditStatus;
use App\SysAdmin\Observability\Services\EnvironmentAuditor;
use Illuminate\Support\Facades\Config;

test('environment auditor returns audit report with all checks', function () {
    // Mock configuration values
    Config::set('setup.requirements.php_version', '8.4.0');
    Config::set('setup.requirements.extensions', ['pdo', 'json']);
    Config::set('setup.requirements.recommended_extensions', ['imagick']);

    $auditor = new EnvironmentAuditor;
    $report = $auditor->audit();

    expect($report)->toBeInstanceOf(AuditReport::class);

    $checks = $report->checks;
    expect($checks)->not->toBeEmpty();

    // Verify php version check exists
    $phpVersionCheck = collect($checks)->firstWhere('nameKey', 'php_version');
    expect($phpVersionCheck)->not->toBeNull();
    expect($phpVersionCheck->status)->toBeIn([AuditStatus::PASS, AuditStatus::FAIL]);

    // Verify extension checks exist
    $pdoCheck = collect($checks)->first(
        fn ($c) => $c->nameKey === 'extension' && $c->nameParams['extension'] === 'pdo',
    );
    expect($pdoCheck)->not->toBeNull();
    expect($pdoCheck->status)->toBe(AuditStatus::PASS); // pdo is loaded by phpunit anyway
});
