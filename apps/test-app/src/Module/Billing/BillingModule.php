<?php

declare(strict_types=1);

namespace App\Module\Billing;

use Nexum\Kernel\Attribute\NexumModule;
use Nexum\Kernel\Module\AbstractModule;

#[NexumModule(
    id: 'billing',
    description: 'Example billing module used to validate the Nexum module layout.',
)]
final class BillingModule extends AbstractModule
{
}
