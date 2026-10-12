<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Support;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Sillbeam\SillbeamBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;

final class TestApplicationKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(private readonly SourceWorkspace $source, private readonly bool $doctrine = false, string $environment = 'test', bool $debug = true)
    {
        parent::__construct($environment, $debug);
    }

    public function getProjectDir(): string
    {
        return $this->source->directory;
    }

    public function getCacheDir(): string
    {
        return $this->source->directory.'/var/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->source->directory.'/var/log';
    }

    protected function getContainerClass(): string
    {
        return $this->source->namespace.ucfirst($this->environment).($this->debug ? 'Debug' : '').'Container';
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        if ($this->doctrine) {
            yield new DoctrineBundle();
        }
        yield new SillbeamBundle();
    }
}
