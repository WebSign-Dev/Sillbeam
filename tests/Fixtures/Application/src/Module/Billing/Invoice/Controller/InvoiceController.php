<?php

declare(strict_types=1);

namespace App\Module\Billing\Invoice\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class InvoiceController
{
    #[Route('/api/billing/invoices', name: 'billing_invoice_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(['data' => []]);
    }
}
