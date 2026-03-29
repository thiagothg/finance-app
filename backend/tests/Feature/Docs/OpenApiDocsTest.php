<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use function Pest\Laravel\get;
it('serves a valid OpenAPI document', function (): void {
    Gate::define('viewApiDocs', fn (?User $user) => true);

    get('/docs/api.json')
        ->assertOk()
        ->assertJsonStructure([
            'openapi',
            'info' => ['title', 'version'],
            'paths',
        ]);
});

it('documents the transactions endpoint', function (): void {
    Gate::define('viewApiDocs', fn (?User $user) => true);

    $response = get('/docs/api.json');

    $paths = $response->json('paths');

    expect($paths)->toHaveKey('/v1/transactions');
});
