<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDoctrineIsAutomaticOrExplicitlyDisabled(): void
    {
        $processor = new Processor();
        self::assertNull($processor->processConfiguration(new Configuration(), [])['doctrine']['enabled']);
        self::assertFalse($processor->processConfiguration(new Configuration(), [['doctrine' => false]])['doctrine']['enabled']);
    }

    public function testFoundationSettingsAreRejectedWithMigrationFeedback(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        (new Processor())->processConfiguration(new Configuration(), [['security' => ['enabled' => true]]]);
    }
}
