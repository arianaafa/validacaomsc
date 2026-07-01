<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Lead\LeadProvisioningService;
use Illuminate\Http\JsonResponse;

final class ListLeadRequestsController extends Controller
{
    public function __construct(
        private readonly LeadProvisioningService $leadProvisioningService,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'lead_requests' => $this->leadProvisioningService->listLeads(),
        ]);
    }
}
