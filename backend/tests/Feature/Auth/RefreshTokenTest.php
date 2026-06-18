<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_refresh_token(): void
    {
        $user = User::factory()->create();

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $oldToken = $loginResponse->json('access_token');

        $response = $this
            ->withToken($oldToken)
            ->postJson('/api/refresh');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'access_token',
                'expires_at',
            ]);

        $newToken = $response->json('access_token');
        $this->assertNotSame($oldToken, $newToken);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->app['auth']->forgetGuards();

        $this
            ->withToken($newToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_refresh_requires_authentication(): void
    {
        $response = $this->postJson('/api/refresh');

        $response->assertUnauthorized();
    }
}
