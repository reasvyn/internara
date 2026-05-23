<?php

declare(strict_types=1);

describe('SetupWizard', function () {
    it('returns 404 when already installed without authorized session', function () {
        $this->get(route('setup'))
            ->assertStatus(404);
    });
});
