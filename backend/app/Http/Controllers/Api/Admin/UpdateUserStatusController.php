<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;

final class UpdateUserStatusController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
    ) {}

    public function __invoke(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $payload = $this->adminUserService->updateActiveStatus(
            $user,
            $request->boolean('is_active'),
        );

        $message = $payload['is_active']
            ? 'Usuário ativado com sucesso.'
            : 'Usuário desativado com sucesso. Todas as sessões foram encerradas.';

        return response()->json([
            'message' => $message,
            'user' => $payload,
        ]);
    }
}
