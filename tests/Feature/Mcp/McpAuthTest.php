<?php

use App\Models\User;

it('returns 401 when no authorization header is provided', function () {
    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id'      => '1',
        'method'  => 'tools/list',
        'params'  => [],
    ])->assertStatus(401);
});

it('returns 401 for an invalid bearer token', function () {
    $this->withToken('invalid-token-xyz')
        ->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id'      => '1',
            'method'  => 'tools/list',
            'params'  => [],
        ])->assertStatus(401);
});

it('returns 401 for a revoked token', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('my-token');

    $token->accessToken->delete();

    $this->withToken($token->plainTextToken)
        ->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id'      => '1',
            'method'  => 'tools/list',
            'params'  => [],
        ])->assertStatus(401);
});

it('returns a successful response with a valid token', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('my-token');

    $this->withToken($token->plainTextToken)
        ->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id'      => '1',
            'method'  => 'tools/list',
            'params'  => [],
        ])->assertStatus(200);
});

it('tools/list with valid token returns non-empty tools array', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('my-token');

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id'      => '1',
            'method'  => 'tools/list',
            'params'  => [],
        ]);

    $response->assertStatus(200);
    $tools = $response->json('result.tools');
    expect($tools)->toBeArray()->not->toBeEmpty();
});
