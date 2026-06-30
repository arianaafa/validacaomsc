<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

final class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payload = $this->authService->changePassword(
            $user,
            $request->string('password')->toString(),
        );

        return response()->json($payload);
    }
}
