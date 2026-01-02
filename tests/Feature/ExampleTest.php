<?php

declare(strict_types=1);

test('the application returns a successful response', function () {
    $response = $this->get('/api/');

    $response->assertStatus(200);
});
