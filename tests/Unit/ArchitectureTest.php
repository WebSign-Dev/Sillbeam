<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Unit;

use App\Module\Billing\BillingModule;
use App\Module\Identity\IdentityModule;
use PHPUnit\Framework\TestCase;
use Sillbeam\Architecture\ArchitectureCompiler;
use Sillbeam\Architecture\ArchitectureDiscovery;
use Sillbeam\Architecture\DiscoveryOptions;
use Sillbeam\Architecture\DiscoveryResult;
use Sillbeam\Module\ModuleDefinition;
use Sillbeam\Project\ProjectDefinition;

final class ArchitectureTest extends TestCase
{
    public function testDiscoversParameterlessDescriptorsWithoutSymfonyServices(): void
    {
        $root = \dirname(__DIR__).'/Fixtures/Application/src';
        $result = (new ArchitectureDiscovery())->discover(new DiscoveryOptions($root, 'App\\'));

        self::assertSame('billing', $result->modules[BillingModule::class]->id);
        self::assertSame('identity', $result->modules[IdentityModule::class]->id);
        self::assertFalse($result->modules[BillingModule::class]->isPrivate());
        self::assertSame([], $result->projects);
    }

    public function testRejectsProjectDescriptorNestedInsideProject(): void
    {
        $root = \dirname(__DIR__).'/Fixtures/Invalid/NestedProject/src';

        $this->expectExceptionMessage('overlapping project');
        (new ArchitectureDiscovery())->discover(new DiscoveryOptions($root, 'InvalidNestedProject\\'));
    }

    public function testRejectsNestedModuleDescriptor(): void
    {
        $root = \dirname(__DIR__).'/Fixtures/Invalid/NestedModule/src';

        $this->expectExceptionMessage('overlapping module');
        (new ArchitectureDiscovery())->discover(new DiscoveryOptions($root, 'InvalidNestedModule\\'));
    }

    public function testApplicationModeActivatesEveryEnabledSharedModule(): void
    {
        $result = $this->discoveryResult([
            TestAModule::class => $this->module('a', TestAModule::class, [TestBModule::class]),
            TestBModule::class => $this->module('b', TestBModule::class),
        ]);
        $compiled = (new ArchitectureCompiler())->compile($result, ['modules' => [], 'projects' => []]);

        self::assertSame([TestBModule::class, TestAModule::class], $compiled->applicationModules);
        self::assertSame([], $compiled->projects);
    }

    public function testDisabledDependencyOnlyFailsWhenItsGraphIsActive(): void
    {
        $project = new ProjectDefinition('shop', TestShopProject::class, '', [], [], '/tmp/Shop', '/tmp/Shop.php', 'Test\\Shop', [], []);
        $unused = $this->module('unused', TestUnusedModule::class, [TestDisabledModule::class]);
        $result = $this->discoveryResult([
            TestUnusedModule::class => $unused,
            TestDisabledModule::class => $this->module('disabled', TestDisabledModule::class),
        ], [TestShopProject::class => $project]);

        $compiled = (new ArchitectureCompiler())->compile($result, [
            'modules' => ['disabled' => ['enabled' => false]],
            'projects' => ['shop' => ['hosts' => []]],
        ]);
        self::assertSame([], $compiled->projectModules[TestShopProject::class]);
    }

    public function testSharedModuleCannotDependOnPrivateModule(): void
    {
        $project = new ProjectDefinition('shop', TestShopProject::class, '', [], [], '/tmp/Shop', '/tmp/Shop.php', 'Test\\Shop', [], []);
        $result = $this->discoveryResult([
            TestSharedModule::class => $this->module('shared', TestSharedModule::class, [TestPrivateModule::class]),
            TestPrivateModule::class => $this->module('private', TestPrivateModule::class, project: TestShopProject::class),
        ], [TestShopProject::class => $project]);

        $this->expectExceptionMessage('cannot depend on private module');
        (new ArchitectureCompiler())->compile($result, ['modules' => [], 'projects' => ['shop' => ['hosts' => []]]]);
    }

    public function testDisabledTransitiveDependencyFailsForAnActiveProjectGraph(): void
    {
        $project = new ProjectDefinition('shop', TestShopProject::class, '', [TestAModule::class], [], '/tmp/Shop', '/tmp/Shop.php', 'Test\\Shop', [], []);
        $result = $this->discoveryResult([
            TestAModule::class => $this->module('a', TestAModule::class, [TestBModule::class]),
            TestBModule::class => $this->module('b', TestBModule::class),
        ], [TestShopProject::class => $project]);

        $this->expectExceptionMessage('requires disabled module "b"');
        (new ArchitectureCompiler())->compile($result, [
            'modules' => ['b' => ['enabled' => false]],
            'projects' => ['shop' => ['hosts' => []]],
        ]);
    }

    public function testDependencyCyclesAlwaysFail(): void
    {
        $result = $this->discoveryResult([
            TestAModule::class => $this->module('a', TestAModule::class, [TestBModule::class]),
            TestBModule::class => $this->module('b', TestBModule::class, [TestAModule::class]),
        ]);

        $this->expectExceptionMessage('Circular Sillbeam module dependency');
        (new ArchitectureCompiler())->compile($result, ['modules' => [], 'projects' => []]);
    }

    public function testDisabledPrivateModuleIsNotAnAutomaticActivationRoot(): void
    {
        $project = new ProjectDefinition('shop', TestShopProject::class, '', [], [], '/tmp/Shop', '/tmp/Shop.php', 'Test\\Shop', [], []);
        $result = $this->discoveryResult([
            TestPrivateModule::class => $this->module('private', TestPrivateModule::class, project: TestShopProject::class),
        ], [TestShopProject::class => $project]);
        $compiled = (new ArchitectureCompiler())->compile($result, ['modules' => ['project:shop:private' => ['enabled' => false]]]);
        self::assertSame([], $compiled->usedModules());
        self::assertFalse($compiled->modules[TestPrivateModule::class]->enabled);
    }

    /**
     * @param array<class-string, ModuleDefinition>  $modules
     * @param array<class-string, ProjectDefinition> $projects
     */
    private function discoveryResult(array $modules, array $projects = []): DiscoveryResult
    {
        return new DiscoveryResult('/tmp', 'Test\\', $modules, $projects, []);
    }

    /**
     * @param class-string       $class
     * @param list<class-string> $dependencies
     * @param class-string|null  $project
     */
    private function module(string $id, string $class, array $dependencies = [], ?string $project = null): ModuleDefinition
    {
        return new ModuleDefinition($id, $class, '', $dependencies, true, '/tmp/'.$id, '/tmp/'.$id.'.php', 'Test\\'.$id, [], [], $project);
    }
}

final class TestAModule
{
}
final class TestBModule
{
}
final class TestUnusedModule
{
}
final class TestDisabledModule
{
}
final class TestSharedModule
{
}
final class TestPrivateModule
{
}
final class TestShopProject
{
}
