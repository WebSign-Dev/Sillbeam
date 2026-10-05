<?php

declare(strict_types=1);

namespace Nexum\Tests\Integration;

use App\Kernel;
use App\Module\Billing\BillingModule;
use App\NexumTestKernel;
use Nexum\Module\ModuleRegistryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\RouterInterface;

final class KernelBootTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test', true);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testLoadsLegacyAndEnabledModuleRoutes(): void
    {
        $router = $this->kernel->getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();

        self::assertNotNull($routes->get('app_home'));
        self::assertNotNull($routes->get('legacy_health'));
        self::assertNotNull($routes->get('billing_invoice_list'));
        self::assertNull($routes->get('nexum_management_modules'));
        self::assertNull($routes->get('nexum_management_module_update'));
    }

    public function testModuleRegistryAndAboutCommandAreAvailable(): void
    {
        $registry = $this->kernel->getContainer()->get(ModuleRegistryInterface::class);
        self::assertInstanceOf(ModuleRegistryInterface::class, $registry);
        self::assertTrue($registry->get(BillingModule::class)->enabled);

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $tester = new CommandTester($application->find('nexum:about'));
        $tester->execute([]);

        self::assertStringContainsString('Nexum', $tester->getDisplay());
        self::assertStringContainsString('Symfony', $tester->getDisplay());
    }

    public function testOptionalNexumKernelHasTheSameRoutesWithoutDuplicates(): void
    {
        $kernel = new NexumTestKernel('test', true);
        try {
            $kernel->boot();
            $router = $kernel->getContainer()->get('router');
            self::assertInstanceOf(RouterInterface::class, $router);
            $routes = $router->getRouteCollection();
            self::assertNotNull($routes->get('app_home'));
            self::assertNotNull($routes->get('billing_invoice_list'));
            self::assertCount(1, array_filter(array_keys($routes->all()), static fn (string|int $name): bool => 'billing_invoice_list' === $name));
        } finally {
            $kernel->shutdown();
        }
    }
}
