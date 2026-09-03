<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('menampilkan halaman beranda dengan status 200', function () {
    $response = $this->get('/');

    $response->assertOk();
});