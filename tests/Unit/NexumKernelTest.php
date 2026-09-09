<?php

declare(strict_types=1);

namespace Nexum\Tests\Unit;

use Nexum\Kernel\NexumKernel;
use PHPUnit\Framework\TestCase;

final class NexumKernelTest extends TestCase
{
    public function testApplicationNameUsesComposerMetadataInsteadOfContainerDirectory(): void
    {
        $kernel = new class('test', false) extends NexumKernel {
            public function getProjectDir(): string
            {
                return '/var/www/html';
            }
        };

        self::assertSame('nexum/monorepo', $kernel->getApplicationName());
    }
}
