<?php

test('returns a successful response', function () {
    $response = $this->get('/about');

    $response->assertOk();
});
