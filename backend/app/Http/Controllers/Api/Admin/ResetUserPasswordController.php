<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;

final class ResetUserPasswordController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
    ) {}

    public function __invoke(ResetUserPasswordRequest $request, User $user): JsonResponse
    {
        $payload = $this->adminUserService->resetPassword(
            $user,
            $request->has('password') ? $request->string('password')->toString() : null,
        );

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
            ...$payload,
        ]);
    }
}
