<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Architecture\ArchitectureDiscovery;
use Sillbeam\Architecture\DiscoveryOptionsResolver;
use Sillbeam\Tests\Support\SourceWorkspace;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FlexibleDiscoveryTest extends TestCase
{
    private SourceWorkspace $source;

    protected function setUp(): void
    {
        $this->source = new SourceWorkspace();
    }

    protected function tearDown(): void
    {
        $this->source->remove();
    }

    /** @param array<string, mixed> $config */
    private function discover(array $config = []): \Sillbeam\Architecture\DiscoveryResult
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->source->directory);

        return (new ArchitectureDiscovery())->discover((new DiscoveryOptionsResolver())->resolve($container, [$config]));
    }

    public function testAttributeDescriptorsHaveNoRequiredFilenameSuffix(): void
    {
        $module = $this->source->php('src/Module/Billing/Billing.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule(id: "billing")] final class Billing {}');
        self::assertArrayHasKey($module, $this->discover()->modules);
    }

    public function testOrdinaryModuleInterfaceDoesNotBecomeADescriptor(): void
    {
        $this->source->php('src/Contract/Module.php', 'interface Module {}');
        self::assertSame([], $this->discover()->modules);
    }

    public function testOrphanPrivateModuleFailsInsteadOfBecomingShared(): void
    {
        $this->source->php('src/Project/Orphan/Module/Secret/SecretModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class SecretModule {}');
        $this->expectExceptionMessage('owning project');
        $this->discover();
    }

    public function testDuplicateModuleAreasAreRejected(): void
    {
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class AModule {}');
        $this->source->php('src/Module/A/BModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class BModule {}');
        $this->expectExceptionMessage('overlapping module');
        $this->discover();
    }

    public function testMultipleRootsExcludesAndAlternativeIntegrationDirectories(): void
    {
        $billing = $this->source->php('src/Domain/Billing/BillingModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class BillingModule {}');
        $shipping = $this->source->php('src/Business/Shipping/ShippingModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class ShippingModule {}');
        $this->source->php('src/Domain/Ignored/IgnoredModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class IgnoredModule {}');
        $this->source->php('src/Domain/Billing/Http/Endpoint.php', 'final class Endpoint {}');
        $this->source->php('src/Domain/Billing/Model/Record.php', 'final class Record {}');
        $result = $this->discover([
            'discovery' => ['modules' => [
                ['path' => 'src/Domain', 'excludes' => ['Ignored/**']],
                ['path' => 'src/Business'],
            ]],
            'integrations' => ['controllers' => ['Http'], 'entities' => ['Model']],
        ]);
        self::assertCount(2, $result->modules);
        self::assertArrayHasKey($shipping, $result->modules);
        self::assertSame([$this->source->directory.'/src/Domain/Billing/Http'], $result->modules[$billing]->controllerDirectories);
        self::assertSame([$this->source->directory.'/src/Domain/Billing/Model'], $result->modules[$billing]->entityDirectories);
    }

    public function testDescriptorsOutsideConfiguredBoundariesFail(): void
    {
        $this->source->php('src/Domain/Billing/BillingModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class BillingModule {}');
        $this->expectExceptionMessage('outside the allowed');
        $this->discover();
    }

    public function testConflictingNamespacesForIdenticalRootsFailDeterministically(): void
    {
        $this->source->php('src/Domain/Billing/Billing.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class Billing {}');
        $this->expectExceptionMessage('Conflicting discovery namespaces');
        $this->discover(['discovery' => ['modules' => [
            ['path' => 'src/Domain', 'namespace' => $this->source->namespace.'\\Domain'],
            ['path' => 'src/Domain', 'namespace' => 'Other\\Domain'],
        ]]]);
    }

    public function testProjectCannotBeNestedInsideAModuleArea(): void
    {
        $this->source->php('src/Module/Shared/SharedModule.php', '#[\Sillbeam\Module\Attribute\SillbeamModule] final class SharedModule {}');
        $this->source->php('src/Module/Shared/Projects/Shop/ShopProject.php', '#[\Sillbeam\Project\Attribute\SillbeamProject] final class ShopProject {}');
        $this->expectExceptionMessage('Project cannot be inside module area');
        $this->discover(['discovery' => ['projects' => [['path' => 'src/Module/Shared/Projects']]]]);
    }
}
