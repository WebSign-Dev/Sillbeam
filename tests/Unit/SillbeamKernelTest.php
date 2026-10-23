<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Kernel\SillbeamKernel;

final class SillbeamKernelTest extends TestCase
{
    public function testApplicationNameUsesComposerMetadataInsteadOfContainerDirectory(): void
    {
        $kernel = new class('test', false) extends SillbeamKernel {
            public function getProjectDir(): string
            {
                return '/var/www/html';
            }
        };

        self::assertSame('websign-dev/sillbeam-core', $kernel->getApplicationName());
    }
}
