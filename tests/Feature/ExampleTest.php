<?php

declare(strict_types=1);

test('it redirects to login page', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
    $response->assertRedirect(route('login'));
});

test('login page is accessible', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
    $response->assertSeeLivewire('auth.login');
});
