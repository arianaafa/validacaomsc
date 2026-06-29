<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Msc\StoreLeadRequest;
use App\Services\Msc\LeadService;
use Illuminate\Http\JsonResponse;

final class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $leadRequest = $this->leadService->createLead($request->payload());

        return response()->json([
            'message' => 'Solicitação recebida com sucesso. Nossa equipe entrará em contato em breve.',
            'lead_request' => $this->leadService->formatLeadRequest($leadRequest),
        ], JsonResponse::HTTP_CREATED);
    }
}
