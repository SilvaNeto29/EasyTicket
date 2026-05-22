<?php

use App\Models\User;
use Livewire\Volt\Volt;

it('authenticated user can view the api tokens section on profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertSee('API Tokens')
        ->assertStatus(200);
});

it('user can generate a token with a valid name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('profile.api-tokens')
        ->set('tokenName', 'My Claude Token')
        ->call('createToken')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id'   => $user->id,
        'tokenable_type' => User::class,
        'name'           => 'My Claude Token',
    ]);
});

it('generated token value is shown once after generation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('profile.api-tokens')
        ->set('tokenName', 'Test Token')
        ->call('createToken')
        ->assertHasNoErrors();

    expect($component->newTokenValue)->not->toBeNull()->toBeString();
});

it('generated token is stored in the database', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('profile.api-tokens')
        ->set('tokenName', 'Stored Token')
        ->call('createToken')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id'   => $user->id,
        'tokenable_type' => User::class,
        'name'           => 'Stored Token',
    ]);
});

it('empty token name is rejected with validation error', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('profile.api-tokens')
        ->set('tokenName', '')
        ->call('createToken')
        ->assertHasErrors(['tokenName']);
});

it('user can revoke a token and it is removed from database', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('Revocable Token');
    $this->actingAs($user);

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);

    Volt::test('profile.api-tokens')
        ->call('revokeToken', $token->accessToken->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
});

it('revoked token returns 401 on subsequent mcp calls', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('Revocable');

    $token->accessToken->delete();

    $this->withToken($token->plainTextToken)
        ->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id'      => '1',
            'method'  => 'tools/list',
            'params'  => [],
        ])->assertStatus(401);
});

it('user with no tokens has an empty token list', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    $other->createToken('Other Token');

    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
});

it('user cannot revoke another users token', function () {
    $user       = User::factory()->create();
    $other      = User::factory()->create();
    $otherToken = $other->createToken('Other Token');
    $this->actingAs($user);

    Volt::test('profile.api-tokens')
        ->call('revokeToken', $otherToken->accessToken->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
});
