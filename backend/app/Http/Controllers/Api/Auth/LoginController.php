<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

final class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $payload = $this->authService->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json($payload);
    }
}
