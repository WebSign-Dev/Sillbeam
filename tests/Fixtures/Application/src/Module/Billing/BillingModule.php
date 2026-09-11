<?php

declare(strict_types=1);

namespace App\Module\Billing;

use Nexum\Module\Attribute\NexumModule;

#[NexumModule(
    id: 'billing',
    description: 'Example billing module used to validate the Nexum module layout.',
)]
final class BillingModule
{
}
