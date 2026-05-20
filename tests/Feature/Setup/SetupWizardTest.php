<?php

declare(strict_types=1);

describe('SetupWizard', function () {
    it('redirects to login when already installed', function () {
        $this->get(route('setup'))
            ->assertRedirect(route('login'));
    });
});
