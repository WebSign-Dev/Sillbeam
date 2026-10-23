<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Support;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Filesystem\Filesystem;

final class SourceWorkspace
{
    public readonly string $directory;
    public readonly string $namespace;
    private ClassLoader $loader;
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->directory = sys_get_temp_dir().'/sillbeam-source-'.bin2hex(random_bytes(6));
        $this->namespace = 'SillbeamFixture'.bin2hex(random_bytes(6));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->directory.'/src');
        $this->loader = new ClassLoader();
        $this->loader->addPsr4($this->namespace.'\\', $this->directory.'/src');
        $this->loader->register(true);
        $this->write('composer.json', json_encode(['autoload' => ['psr-4' => [$this->namespace.'\\' => 'src/']]], \JSON_THROW_ON_ERROR));
    }

    public function write(string $path, string $contents): void
    {
        $this->filesystem->dumpFile($this->directory.'/'.$path, $contents);
    }

    /** @return class-string */
    public function php(string $path, string $body): string
    {
        $relative = substr($path, 4, -4);
        $namespace = $this->namespace.'\\'.str_replace('/', '\\', \dirname($relative));
        $namespace = rtrim(str_replace('\\.', '', $namespace), '\\');
        $this->write($path, '<?php namespace '.$namespace.'; '.$body);

        /** @var class-string $class */
        $class = $this->namespace.'\\'.str_replace('/', '\\', $relative);

        return $class;
    }

    public function remove(): void
    {
        $this->loader->unregister();
        $this->filesystem->remove($this->directory);
    }
}
