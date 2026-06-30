<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AdminUserService
{
    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     municipality_id: int|null,
     *     force_password_change: bool,
     *     is_active: bool
     * }>
     */
    public function listUsers(): array
    {
        return User::query()
            ->where('is_superadmin', false)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'municipality_id' => $user->municipality_id,
                'force_password_change' => $user->force_password_change,
                'is_active' => $user->isActive(),
            ])
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     municipality_id: int|null,
     *     force_password_change: bool,
     *     is_active: bool
     * }
     */
    public function updateActiveStatus(User $targetUser, bool $isActive): array
    {
        if ($targetUser->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'user_id' => ['Não é permitido alterar o status de um SuperAdmin.'],
            ]);
        }

        $targetUser->update(['is_active' => $isActive]);

        if (! $isActive) {
            $targetUser->tokens()->delete();
        }

        $targetUser->refresh();

        return [
            'id' => $targetUser->id,
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'municipality_id' => $targetUser->municipality_id,
            'force_password_change' => $targetUser->force_password_change,
            'is_active' => $targetUser->isActive(),
        ];
    }

    /**
     * @return array{
     *     user: array{id: int, name: string, email: string},
     *     temporary_password: string|null,
     *     force_password_change: bool
     * }
     */
    public function resetPassword(User $targetUser, ?string $newPassword = null): array
    {
        if ($targetUser->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'user_id' => ['Não é permitido redefinir a senha de um SuperAdmin.'],
            ]);
        }

        $temporaryPassword = $newPassword ?? Str::password(16);

        $targetUser->update([
            'password' => $temporaryPassword,
            'force_password_change' => true,
        ]);

        return [
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
            'temporary_password' => $newPassword === null ? $temporaryPassword : null,
            'force_password_change' => true,
        ];
    }
}
