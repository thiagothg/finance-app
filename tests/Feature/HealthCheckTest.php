<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('returns a successful health check response', function () {
    $response = getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
        ]);
});
