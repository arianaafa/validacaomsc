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
     *     force_password_change: bool
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
            ])
            ->all();
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
