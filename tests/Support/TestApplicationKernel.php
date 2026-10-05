<?php

declare(strict_types=1);

namespace Nexum\Tests\Support;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Nexum\NexumBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;

final class TestApplicationKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(private readonly SourceWorkspace $source, private readonly bool $doctrine = false)
    {
        parent::__construct('test', true);
    }

    public function getProjectDir(): string
    {
        return $this->source->directory;
    }

    public function getCacheDir(): string
    {
        return $this->source->directory.'/var/cache';
    }

    public function getLogDir(): string
    {
        return $this->source->directory.'/var/log';
    }

    protected function getContainerClass(): string
    {
        return $this->source->namespace.'Container';
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        if ($this->doctrine) {
            yield new DoctrineBundle();
        }
        yield new NexumBundle();
    }
}
