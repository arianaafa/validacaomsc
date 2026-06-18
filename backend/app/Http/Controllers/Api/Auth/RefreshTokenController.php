<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RefreshTokenController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $plainTextToken = $request->bearerToken();

        if ($plainTextToken === null) {
            return response()->json([
                'message' => 'Token não informado.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $payload = $this->authService->refreshToken($user, $plainTextToken);

        return response()->json($payload);
    }
}
