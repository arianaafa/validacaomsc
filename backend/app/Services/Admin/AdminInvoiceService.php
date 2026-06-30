<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

final class AdminInvoiceService
{
    /**
     * @return list<array{
     *     id: int,
     *     municipality_id: int,
     *     municipality_name: string,
     *     amount: string,
     *     status: string,
     *     due_date: string,
     *     created_at: string|null
     * }>
     */
    public function listPendingInvoices(): array
    {
        return Invoice::query()
            ->with('municipality:id,name')
            ->where('status', InvoiceStatus::Pending)
            ->orderBy('due_date')
            ->get()
            ->map(fn (Invoice $invoice): array => $this->formatInvoice($invoice))
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     municipality_id: int,
     *     municipality_name: string,
     *     amount: string,
     *     status: string,
     *     due_date: string,
     *     created_at: string|null
     * }
     */
    public function formatInvoice(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'municipality_id' => $invoice->municipality_id,
            'municipality_name' => $invoice->municipality?->name ?? '',
            'amount' => number_format((float) $invoice->amount, 2, '.', ''),
            'status' => $invoice->status->value,
            'due_date' => $invoice->due_date->toDateString(),
            'created_at' => $invoice->created_at?->toIso8601String(),
        ];
    }
}
