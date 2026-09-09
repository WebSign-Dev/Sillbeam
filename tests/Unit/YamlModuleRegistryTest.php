<?php

declare(strict_types=1);

namespace Nexum\Tests\Unit;

use Nexum\Kernel\Module\YamlModuleRegistry;
use PHPUnit\Framework\TestCase;

final class YamlModuleRegistryTest extends TestCase
{
    public function testDisabledModuleIsNotReturnedAsEnabled(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'nexum-modules-');
        self::assertIsString($file);

        try {
            file_put_contents($file, <<<'YAML'
                version: 1
                modules:
                    billing:
                        class: App\Module\Billing\BillingModule
                        enabled: false
                YAML);

            $registry = new YamlModuleRegistry($file);

            self::assertTrue($registry->has('billing'));
            self::assertFalse($registry->get('billing')->enabled);
            self::assertSame([], $registry->enabled());
        } finally {
            unlink($file);
        }
    }
}
