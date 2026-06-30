<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;

final class AuthService
{
    private const TOKEN_NAME = 'api-token';

    private const TOKEN_TTL_HOURS = 8;

    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    /**
     * @return array{user: array{id: int, name: string, email: string}, access_token: string, expires_at: string|null}
     */
    public function login(string $email, string $password): array
    {
        if (! $this->auth->attempt(['email' => $email, 'password' => $password])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user = $this->auth->user();

        return $this->issueTokenResponse($user);
    }

    /**
     * @return array{user: array{id: int, name: string, email: string}, access_token: string, expires_at: string|null}
     */
    public function register(string $name, string $email, string $password): array
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        return $this->issueTokenResponse($user);
    }

    public function logout(User $user, ?string $plainTextToken = null): void
    {
        if ($plainTextToken !== null) {
            $this->revokePersonalAccessToken($user, $plainTextToken);

            return;
        }

        $this->revokeCurrentAccessToken($user);
    }

    /**
     * @return array{user: array{id: int, name: string, email: string}, access_token: string, expires_at: string|null}
     */
    public function refreshToken(User $user, string $plainTextToken): array
    {
        $this->revokePersonalAccessToken($user, $plainTextToken);

        return $this->issueTokenResponse($user);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     is_superadmin: bool,
     *     force_password_change: bool,
     *     municipality_id: int|null
     * }
     */
    public function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_superadmin' => $user->isSuperAdmin(),
            'force_password_change' => $user->force_password_change,
            'municipality_id' => $user->municipality_id,
        ];
    }

    private function revokePersonalAccessToken(User $user, string $plainTextToken): void
    {
        $token = PersonalAccessToken::findToken($plainTextToken);

        if ($token !== null && (int) $token->tokenable_id === (int) $user->id) {
            $token->delete();
        }
    }

    private function revokeCurrentAccessToken(User $user): void
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken instanceof TransientToken) {
            return;
        }

        if ($currentToken instanceof PersonalAccessToken) {
            $currentToken->delete();
        }
    }

    /**
     * @return array{user: array{id: int, name: string, email: string}, access_token: string, expires_at: string|null}
     */
    private function issueTokenResponse(User $user): array
    {
        $token = $user->createToken(
            self::TOKEN_NAME,
            ['*'],
            Carbon::now()->addHours(self::TOKEN_TTL_HOURS),
        );

        return [
            'user' => $this->formatUser($user),
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ];
    }
}
