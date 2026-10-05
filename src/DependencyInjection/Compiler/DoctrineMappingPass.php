<?php

declare(strict_types=1);

namespace Nexum\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/** Keeps Nexum exclusions local to generated mappings, preserving native Doctrine mappings. */
final class DoctrineMappingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('nexum.doctrine.mappings')) {
            return;
        }
        /** @var list<array{prefix: string, directory: string, excludes: list<string>}> $mappings */
        $mappings = $container->getParameter('nexum.doctrine.mappings');
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!str_ends_with($id, '_metadata_driver')) {
                continue;
            }
            $calls = $definition->getMethodCalls();
            foreach ($calls as $index => [$method, $arguments]) {
                if ('addDriver' !== $method || !($arguments[0] ?? null) instanceof Reference) {
                    continue;
                }
                foreach ($mappings as $mapping) {
                    if (($arguments[1] ?? null) !== $mapping['prefix']) {
                        continue;
                    }
                    $driver = $container->findDefinition((string) $arguments[0]);
                    // A native override of the generated mapping remains authoritative.
                    if ('Doctrine\\ORM\\Mapping\\Driver\\AttributeDriver' !== $driver->getClass() || !\in_array($mapping['directory'], $driver->getArgument(0), true)) {
                        continue;
                    }
                    $driverId = 'nexum.doctrine.driver.'.hash('xxh128', $id.$mapping['directory']);
                    $container->register($driverId, 'Doctrine\\ORM\\Mapping\\Driver\\AttributeDriver')
                        ->setArguments([[$mapping['directory']]])->addMethodCall('addExcludePaths', [$mapping['excludes']]);
                    $calls[$index][1][0] = new Reference($driverId);
                }
            }
            // Specific private module prefixes must precede their enclosing Project prefix.
            usort($calls, static fn (array $left, array $right): int => ('addDriver' === $left[0] && 'addDriver' === $right[0]) ? \strlen($right[1][1]) <=> \strlen($left[1][1]) : 0);
            $definition->setMethodCalls($calls);
        }
    }
}
