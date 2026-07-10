<?php

declare(strict_types=1);

use App\Settings\Actions\TestMailSettingsAction;

test('test mail returns false for invalid config', function () {
    $action = new TestMailSettingsAction;

    $result = $action->execute('test@example.com', [
        'host' => '',
        'port' => '',
        'encryption' => '',
        'username' => '',
        'password' => '',
        'from_address' => '',
        'from_name' => '',
    ]);

    expect($result)->toBeFalse();
});
