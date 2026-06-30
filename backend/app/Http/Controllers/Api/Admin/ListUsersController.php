<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;

final class ListUsersController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'users' => $this->adminUserService->listUsers(),
        ]);
    }
}
