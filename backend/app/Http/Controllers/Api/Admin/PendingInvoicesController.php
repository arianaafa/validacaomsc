<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminInvoiceService;
use Illuminate\Http\JsonResponse;

final class PendingInvoicesController extends Controller
{
    public function __construct(
        private readonly AdminInvoiceService $adminInvoiceService,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'invoices' => $this->adminInvoiceService->listPendingInvoices(),
        ]);
    }
}
