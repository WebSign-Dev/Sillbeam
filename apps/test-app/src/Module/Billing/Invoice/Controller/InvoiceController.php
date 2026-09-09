<?php

declare(strict_types=1);

namespace App\Module\Billing\Invoice\Controller;

use App\Module\Billing\Invoice\Entity\Invoice;
use Nexum\Api\Attribute\ApiResource;
use Nexum\Api\Controller\AbstractResourceController;
use Nexum\Api\Scope\ResourceScope;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/billing/invoices', name: 'billing_invoice_')]
#[ApiResource(
    entity: Invoice::class,
    writableFields: ['number'],
    requiredFields: ['number'],
    filters: [
        'number' => ['eq', 'contains', 'in'],
        'createdAt' => ['gte', 'lte'],
    ],
    sortableFields: ['id', 'number', 'createdAt', 'updatedAt'],
    defaultSort: ['createdAt' => 'desc', 'id' => 'desc'],
    defaultPageSize: 20,
    maxPageSize: 100,
    permissionPrefix: 'billing.invoice',
    scope: ResourceScope::SUBJECT,
    scopeField: 'owner',
)]
final class InvoiceController extends AbstractResourceController
{
}
