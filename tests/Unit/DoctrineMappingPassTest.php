<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use PHPUnit\Framework\TestCase;
use Sillbeam\Core\DependencyInjection\Compiler\DoctrineMappingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DoctrineMappingPassTest extends TestCase
{
    public function testGeneratedMappingMatchesDriverDirectoriesWithMixedSeparators(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sillbeam.doctrine.mappings', [
            ['prefix' => 'App\\Entity', 'directory' => '/app/src/Entity', 'excludes' => ['/app/src/Entity/Hidden.php']],
        ]);
        $container->register('attributes', AttributeDriver::class)->setArguments([['/app\\src/Entity/']]);
        $chain = $container->register('doctrine.orm.default_metadata_driver');
        $chain->addMethodCall('addDriver', [new Reference('attributes'), 'App\\Entity']);

        (new DoctrineMappingPass())->process($container);

        $driver = (string) $chain->getMethodCalls()[0][1][0];
        self::assertNotSame('attributes', $driver);
        self::assertSame([['/app/src/Entity']], $container->findDefinition($driver)->getArguments());
    }

    public function testNativeMappingPrecedenceIsPreserved(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sillbeam.doctrine.mappings', []);
        $chain = $container->register('doctrine.orm.default_metadata_driver');
        $chain->addMethodCall('addDriver', [new Reference('native.parent'), 'App']);
        $chain->addMethodCall('addDriver', [new Reference('native.child'), 'App\\Entity']);

        (new DoctrineMappingPass())->process($container);

        self::assertSame(['App', 'App\\Entity'], array_column(array_column($chain->getMethodCalls(), 1), 1));
    }

    public function testGeneratedChildMappingsPrecedeTheirParentWithoutSortingNativeMappings(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sillbeam.doctrine.mappings', [
            ['prefix' => 'App\\Project\\Entity', 'directory' => '/parent', 'excludes' => ['/parent/Private/Entity/Record.php']],
            ['prefix' => 'App\\Project\\Entity\\Private\\Entity', 'directory' => '/child', 'excludes' => []],
        ]);
        $container->register('attributes', AttributeDriver::class)->setArguments([['/parent', '/child']]);
        $chain = $container->register('doctrine.orm.default_metadata_driver');
        $chain->addMethodCall('addDriver', [new Reference('attributes'), 'App\\Project\\Entity']);
        $chain->addMethodCall('addDriver', [new Reference('attributes'), 'App\\Project\\Entity\\Private\\Entity']);
        $chain->addMethodCall('addDriver', [new Reference('native.parent'), 'Legacy']);
        $chain->addMethodCall('addDriver', [new Reference('native.child'), 'Legacy\\Entity']);

        (new DoctrineMappingPass())->process($container);

        self::assertSame(['App\\Project\\Entity\\Private\\Entity', 'App\\Project\\Entity', 'Legacy', 'Legacy\\Entity'], array_column(array_column($chain->getMethodCalls(), 1), 1));
        $parent = $container->findDefinition((string) $chain->getMethodCalls()[1][1][0]);
        self::assertSame([['/parent']], $parent->getArguments());
        self::assertSame([['addExcludePaths', [['/parent/Private/Entity/Record.php']]]], $parent->getMethodCalls());
    }
}
