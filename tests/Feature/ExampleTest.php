<?php

test('the root url redirects to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirectToRoute('filament.dashboard.auth.login');
});
