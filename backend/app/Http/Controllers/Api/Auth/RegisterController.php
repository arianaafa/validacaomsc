<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

final class RegisterController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $payload = $this->authService->register(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json($payload, JsonResponse::HTTP_CREATED);
    }
}
