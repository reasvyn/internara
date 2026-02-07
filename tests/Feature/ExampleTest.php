<?php

declare(strict_types=1);

test('it performs a basic feature test', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
});
