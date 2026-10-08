<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use HybridApp\Kernel as HybridKernel;
use HybridApp\Module\Backoffice\BackofficeModule;
use HybridApp\Module\Common\CommonModule;
use HybridApp\Module\Unused\Service\UnusedService;
use HybridApp\Project\Admin\AdminProject;
use HybridApp\Project\PublicSite\PublicProject;
use HybridApp\Service\ApplicationStatus;
use ModulesOnlyApp\Kernel as ModulesOnlyKernel;
use ModulesOnlyApp\Module\Archived\ArchivedModule;
use ModulesOnlyApp\Module\Foundation\FoundationModule;
use ModulesOnlyApp\Module\Foundation\Service\ModuleClock;
use ModulesOnlyApp\Module\Inventory\InventoryModule;
use PHPUnit\Framework\TestCase;
use ProjectOnlyApp\Kernel as ProjectOnlyKernel;
use ProjectOnlyApp\Project\Portal\Entity\ProjectRecord;
use ProjectOnlyApp\Project\Portal\PortalProject as ProjectOnlyPortal;
use ProjectOnlyApp\Project\Portal\Service\ProjectGreeting;
use Psr\Container\ContainerInterface;
use Sillbeam\Module\ModuleRegistryInterface;
use Sillbeam\Project\ProjectContextInterface;
use Sillbeam\Project\ProjectRegistryInterface;
use SingleProjectApp\Kernel as SingleProjectKernel;
use SingleProjectApp\Module\Profile\ProfileModule;
use SingleProjectApp\Project\Portal\Module\Dashboard\DashboardModule;
use SingleProjectApp\Project\Portal\PortalProject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use SymfonyOnlyApp\Kernel as SymfonyOnlyKernel;
use SymfonyOnlyApp\Service\Greeting;

final class ApplicationVariantsTest extends TestCase
{
    /** @var list<KernelInterface> */
    private array $kernels = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->kernels) as $kernel) {
            $kernel->shutdown();
        }
        $this->kernels = [];
    }

    public function testModulesOnlyApplicationDiscoversServicesAndExcludesDisabledModule(): void
    {
        $kernel = $this->boot(new ModulesOnlyKernel('test', true));
        $container = $kernel->getContainer();
        $modules = $container->get(ModuleRegistryInterface::class);
        self::assertInstanceOf(ModuleRegistryInterface::class, $modules);
        self::assertSame(['foundation', 'inventory'], array_map(
            static fn (string $class): string => $modules->get($class)->id,
            array_keys($modules->active()),
        ));
        self::assertFalse($modules->get(ArchivedModule::class)->enabled);
        self::assertTrue($modules->get(InventoryModule::class)->enabled);
        self::assertSame([FoundationModule::class], $modules->get(InventoryModule::class)->dependencies);

        $services = $container->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $services);
        self::assertTrue($services->has(ModuleClock::class));
        self::assertFalse($services->has(ArchivedModule::class));

        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        self::assertNotNull($router->getRouteCollection()->get('inventory_index'));
        self::assertNull($router->getRouteCollection()->get('archived_index'));
        self::assertSame('inventory-foundation', $kernel->handle(Request::create('/inventory'))->getContent());
        self::assertSame(404, $kernel->handle(Request::create('/archived'))->getStatusCode());

        $context = $container->get(ProjectContextInterface::class);
        self::assertInstanceOf(ProjectContextInterface::class, $context);
        self::assertNull($context->current());
        self::assertTrue($context->hasModule(InventoryModule::class));
        self::assertFalse($context->hasModule(ArchivedModule::class));
    }

    public function testStandardSymfonyApplicationWorksWithoutModulesOrProjects(): void
    {
        $kernel = $this->boot(new SymfonyOnlyKernel('test', true));
        $container = $kernel->getContainer();
        $modules = $container->get(ModuleRegistryInterface::class);
        $projects = $container->get(ProjectRegistryInterface::class);
        self::assertInstanceOf(ModuleRegistryInterface::class, $modules);
        self::assertInstanceOf(ProjectRegistryInterface::class, $projects);
        self::assertSame([], $modules->all());
        self::assertSame([], $projects->all());

        $services = $container->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $services);
        self::assertTrue($services->has(Greeting::class));

        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        self::assertNotNull($router->getRouteCollection()->get('symfony_home'));
        self::assertSame('standard-symfony', $kernel->handle(Request::create('/'))->getContent());

        $context = $container->get(ProjectContextInterface::class);
        self::assertInstanceOf(ProjectContextInterface::class, $context);
        self::assertNull($context->current());
        self::assertFalse($context->hasModule(Greeting::class));
    }

    public function testProjectWorksWithDirectStructureAndWithoutModules(): void
    {
        $kernel = $this->boot(new ProjectOnlyKernel('test', true));
        $container = $kernel->getContainer();
        $projects = $container->get(ProjectRegistryInterface::class);
        $modules = $container->get(ModuleRegistryInterface::class);
        self::assertInstanceOf(ProjectRegistryInterface::class, $projects);
        self::assertInstanceOf(ModuleRegistryInterface::class, $modules);
        self::assertSame([], $modules->all());
        self::assertSame([], $modules->forProject(ProjectOnlyPortal::class));

        $project = $projects->get(ProjectOnlyPortal::class);
        self::assertSame([$project->directory.'/Controller'], $project->controllerDirectories);
        self::assertSame([$project->directory.'/Entity'], $project->entityDirectories);

        $services = $container->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $services);
        self::assertTrue($services->has(ProjectGreeting::class));

        $entityManager = $services->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertSame(ProjectRecord::class, $entityManager->getClassMetadata(ProjectRecord::class)->getName());

        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        self::assertNotNull($router->getRouteCollection()->get('portal.home'));
        self::assertSame('project-without-modules', $kernel->handle(Request::create('https://anything.example/portal'))->getContent());
    }

    public function testSingleProjectNeedsNoHostConfiguration(): void
    {
        $kernel = $this->boot(new SingleProjectKernel('test', true));
        $container = $kernel->getContainer();
        $projects = $container->get(ProjectRegistryInterface::class);
        self::assertInstanceOf(ProjectRegistryInterface::class, $projects);
        self::assertSame(PortalProject::class, $projects->forHost('anything.example')->class);
        self::assertSame([], $projects->get('portal')->hosts);
        $project = $projects->get('portal');
        self::assertSame([$project->directory.'/Controller'], $project->controllerDirectories);
        self::assertSame([$project->directory.'/Entity'], $project->entityDirectories);

        $modules = $container->get(ModuleRegistryInterface::class);
        self::assertInstanceOf(ModuleRegistryInterface::class, $modules);
        self::assertArrayHasKey(ProfileModule::class, $modules->forProject(PortalProject::class));
        self::assertArrayHasKey(DashboardModule::class, $modules->forProject(PortalProject::class));
        $dashboard = $modules->get(DashboardModule::class);
        self::assertSame([$dashboard->directory.'/Controller'], $dashboard->controllerDirectories);
        self::assertSame([$dashboard->directory.'/Entity'], $dashboard->entityDirectories);

        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        self::assertSame('', $router->getRouteCollection()->get('portal.home')?->getHost());
        self::assertNotNull($router->getRouteCollection()->get('portal.profile'));
        self::assertNotNull($router->getRouteCollection()->get('portal.dashboard'));
        self::assertSame('profile', $kernel->handle(Request::create('https://random.example/profile'))->getContent());
        self::assertSame('dashboard', $kernel->handle(Request::create('https://random.example/dashboard'))->getContent());

        $stack = $container->get('request_stack');
        $context = $container->get(ProjectContextInterface::class);
        self::assertInstanceOf(RequestStack::class, $stack);
        self::assertInstanceOf(ProjectContextInterface::class, $context);
        $stack->push(Request::create('https://unconfigured.example/portal'));
        try {
            self::assertSame('portal', $context->id());
            self::assertTrue($context->hasModule(DashboardModule::class));
        } finally {
            $stack->pop();
        }
    }

    public function testHybridApplicationKeepsSymfonyCodeAndScopesSillbeamModules(): void
    {
        $kernel = $this->boot(new HybridKernel('test', true));
        $container = $kernel->getContainer();
        $modules = $container->get(ModuleRegistryInterface::class);
        self::assertInstanceOf(ModuleRegistryInterface::class, $modules);
        self::assertArrayHasKey(CommonModule::class, $modules->forProject(PublicProject::class));
        self::assertArrayHasKey(CommonModule::class, $modules->forProject(AdminProject::class));
        self::assertArrayHasKey(BackofficeModule::class, $modules->forProject(AdminProject::class));
        self::assertArrayNotHasKey(BackofficeModule::class, $modules->forProject(PublicProject::class));

        $services = $container->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $services);
        self::assertTrue($services->has(ApplicationStatus::class));
        self::assertFalse($services->has(UnusedService::class));

        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();
        self::assertNotNull($routes->get('hybrid_health'));
        self::assertNotNull($routes->get('public.common'));
        self::assertNotNull($routes->get('admin.common'));
        self::assertNotNull($routes->get('admin.backoffice'));
        self::assertNull($routes->get('public.backoffice'));
        self::assertSame('public.hybrid.test', $routes->get('public.common')->getDefault('_sillbeam_host'));
        self::assertSame('admin.hybrid.test', $routes->get('admin.backoffice')->getDefault('_sillbeam_host'));
        self::assertSame('ok', $kernel->handle(Request::create('https://public.hybrid.test/health'))->getContent());
        self::assertSame('common', $kernel->handle(Request::create('https://public.hybrid.test/common'))->getContent());
        self::assertSame(404, $kernel->handle(Request::create('https://public.hybrid.test/backoffice'))->getStatusCode());
        self::assertSame('backoffice', $kernel->handle(Request::create('https://admin.hybrid.test/backoffice'))->getContent());
    }

    /** @template T of KernelInterface
     *
     * @param T $kernel
     *
     * @return T
     */
    private function boot(KernelInterface $kernel): KernelInterface
    {
        $kernel->boot();
        $this->kernels[] = $kernel;

        return $kernel;
    }
}
