<?php

declare(strict_types=1);

test('it redirects to login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('login page is accessible', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('password');
});
