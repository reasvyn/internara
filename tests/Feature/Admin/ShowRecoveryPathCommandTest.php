<?php

declare(strict_types=1);

describe('ShowRecoveryPathCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('admin:recovery-path');
    });

    it('shows path and exists status when file exists', function () {
        File::shouldReceive('exists')
            ->with(storage_path('app/private/.recovery-key'))
            ->andReturn(true);

        $this->artisan('admin:recovery-path')
            ->assertExitCode(0);
    });

    it('shows path and missing status when file is missing', function () {
        File::shouldReceive('exists')
            ->with(storage_path('app/private/.recovery-key'))
            ->andReturn(false);

        $this->artisan('admin:recovery-path')
            ->assertExitCode(0);
    });
});
