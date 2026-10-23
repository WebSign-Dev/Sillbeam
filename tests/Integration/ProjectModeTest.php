<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ProjectApp\Kernel;
use ProjectApp\Module\Catalog\CatalogModule;
use ProjectApp\Module\Catalog\EventSubscriber\CatalogSubscriber;
use ProjectApp\Project\Admin\AdminProject;
use ProjectApp\Project\Storefront\Module\Checkout\CheckoutModule as StorefrontCheckout;
use ProjectApp\Project\Storefront\StorefrontProject;
use Psr\Container\ContainerInterface;
use Sillbeam\Core\Module\ModuleRegistryInterface;
use Sillbeam\Core\Project\ProjectContextInterface;
use Sillbeam\Core\Project\ProjectRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class ProjectModeTest extends TestCase
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

    public function testSharedAndPrivateModulesAreAssignedByClass(): void
    {
        $modules = $this->kernel->getContainer()->get(ModuleRegistryInterface::class);
        self::assertInstanceOf(ModuleRegistryInterface::class, $modules);
        self::assertArrayHasKey(CatalogModule::class, $modules->forProject(StorefrontProject::class));
        self::assertArrayHasKey(StorefrontCheckout::class, $modules->forProject(StorefrontProject::class));
        self::assertArrayNotHasKey(CatalogModule::class, $modules->forProject(AdminProject::class));
        self::assertSame('checkout', $modules->get(StorefrontCheckout::class)->id);
        $services = $this->kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $services);
        self::assertTrue($services->has(CatalogSubscriber::class));
        self::assertFalse($services->has('Sillbeam\\Core\\Doctrine\\Store\\EntityStoreInterface'));
        self::assertFalse($services->has('limiter.sillbeam_security_mfa_verify'));
    }

    public function testRoutesAreProjectScopedAndUseCanonicalHosts(): void
    {
        $router = $this->kernel->getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();
        self::assertNotNull($routes->get('storefront.catalog'));
        self::assertNotNull($routes->get('storefront.checkout'));
        self::assertNotNull($routes->get('admin.checkout'));
        self::assertNull($routes->get('admin.catalog'));
        self::assertNull($routes->get('sillbeam_security_token_issue'));
        self::assertNull($routes->get('sillbeam_security_mfa_status'));
        self::assertNull($routes->get('sillbeam_security_passkey_list'));
        self::assertNull($routes->get('sillbeam_management_modules'));

        $router->getContext()->setHost('admin.example.test');
        self::assertSame(
            'http://shop.example.test/catalog',
            $router->generate('storefront.catalog', [], UrlGeneratorInterface::ABSOLUTE_URL),
        );
        self::assertSame('//shop.example.test/catalog', $router->generate('storefront.catalog'));
    }

    public function testProjectContextUsesRequestHostAndAliases(): void
    {
        $context = $this->kernel->getContainer()->get(ProjectContextInterface::class);
        self::assertInstanceOf(ProjectContextInterface::class, $context);
        self::assertNull($context->current());
        $request = Request::create('https://www.example.test/catalog');
        $stack = $this->kernel->getContainer()->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $stack);
        $stack->push($request);
        try {
            self::assertSame('storefront', $context->id());
            self::assertTrue($context->hasModule(CatalogModule::class));
        } finally {
            $stack->pop();
        }
    }

    public function testUnknownHostsFailClosed(): void
    {
        $projects = $this->kernel->getContainer()->get(ProjectRegistryInterface::class);
        self::assertInstanceOf(ProjectRegistryInterface::class, $projects);
        $this->expectException(\OutOfBoundsException::class);
        $projects->forHost('unknown.example.test');
    }

    public function testContextTracksCurrentRequestIncludingSubrequests(): void
    {
        $container = $this->kernel->getContainer();
        $context = $container->get(ProjectContextInterface::class);
        $stack = $container->get('request_stack');
        self::assertInstanceOf(ProjectContextInterface::class, $context);
        self::assertInstanceOf(RequestStack::class, $stack);
        self::assertNull($context->current());
        self::assertFalse($context->hasModule(CatalogModule::class));
        $stack->push(Request::create('https://shop.example.test/'));
        try {
            self::assertTrue($context->is('storefront'));
            $stack->push(Request::create('https://admin.example.test/'));
            try {
                self::assertSame('admin', $context->id());
                self::assertFalse($context->hasModule(CatalogModule::class));
            } finally {
                $stack->pop();
            }
            self::assertTrue($context->hasModule(CatalogModule::class));
            self::assertSame('storefront', $context->id());
        } finally {
            $stack->pop();
        }
        self::assertNull($context->id());
    }
}
