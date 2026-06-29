<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Msc\ListRulesRequest;
use App\Services\Msc\MscRuleService;
use Illuminate\Http\JsonResponse;

final class MscRuleController extends Controller
{
    public function __construct(
        private readonly MscRuleService $mscRuleService,
    ) {}

    public function index(ListRulesRequest $request): JsonResponse
    {
        $paginator = $this->mscRuleService->listRules($request->filters());

        return response()->json($paginator);
    }
}
