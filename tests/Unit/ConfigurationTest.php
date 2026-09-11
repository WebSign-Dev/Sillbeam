<?php

declare(strict_types=1);

namespace Nexum\Tests\Unit;

use Nexum\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testPublicSecuritySurfacesAreDisabledByDefault(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertTrue($config['doctrine']['enabled']);
        self::assertTrue($config['api']['enabled']);
        self::assertTrue($config['security']['enabled']);
        self::assertFalse($config['security']['api']['enabled']);
        self::assertFalse($config['security']['mfa']['enabled']);
        self::assertFalse($config['security']['passkeys']['enabled']);
        self::assertFalse($config['management']['enabled']);
        self::assertFalse($config['user']['enabled']);
    }

    public function testAbilityAndApiExposureAreIndependent(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'security' => [
                'mfa' => ['enabled' => true, 'api' => ['enabled' => false]],
                'passkeys' => ['enabled' => false, 'api' => ['enabled' => false]],
            ],
        ]]);

        self::assertTrue($config['security']['mfa']['enabled']);
        self::assertFalse($config['security']['mfa']['api']['enabled']);
    }
}
