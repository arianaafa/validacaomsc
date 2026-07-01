<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadRequest;
use App\Services\Lead\LeadProvisioningService;
use Illuminate\Http\JsonResponse;

final class StartLeadTrialController extends Controller
{
    public function __construct(
        private readonly LeadProvisioningService $leadProvisioningService,
    ) {}

    public function __invoke(LeadRequest $leadRequest): JsonResponse
    {
        return response()->json(
            $this->leadProvisioningService->startTrial($leadRequest),
            JsonResponse::HTTP_CREATED,
        );
    }
}
