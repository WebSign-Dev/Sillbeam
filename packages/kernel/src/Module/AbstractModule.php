<?php

declare(strict_types=1);

namespace Nexum\Kernel\Module;

use Nexum\Kernel\Attribute\NexumModule;

abstract class AbstractModule implements ModuleInterface
{
    public static function definition(): ModuleDefinition
    {
        $reflection = new \ReflectionClass(static::class);
        $attribute = $reflection->getAttributes(NexumModule::class)[0] ?? null;

        if (null === $attribute) {
            throw new \LogicException(\sprintf('Module "%s" must declare #[NexumModule].', static::class));
        }

        $metadata = $attribute->newInstance();
        $file = $reflection->getFileName();

        if (false === $file) {
            throw new \LogicException(\sprintf('Cannot locate module class "%s".', static::class));
        }

        return new ModuleDefinition(
            id: $metadata->id,
            class: static::class,
            description: $metadata->description,
            dependencies: $metadata->dependencies,
            enabled: true,
            directory: \dirname($file),
        );
    }
}
