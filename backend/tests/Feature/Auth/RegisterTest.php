<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'access_token',
                'expires_at',
            ])
            ->assertJsonPath('user.email', 'maria@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
        ]);
    }

    public function test_register_fails_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
