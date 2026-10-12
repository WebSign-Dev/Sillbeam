<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sillbeam\Tests\Support\SourceWorkspace;

final class ContainerCacheTest extends TestCase
{
    public function testNewArchitectureConfigurationInvalidatesTheDevelopmentContainer(): void
    {
        $source = new SourceWorkspace();
        try {
            $kernel = $source->php('src/Kernel.php', 'final class Kernel extends \\Symfony\\Component\\HttpKernel\\Kernel { use \\Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait; public function getProjectDir(): string { return \\dirname(__DIR__); } }');
            $source->php('src/Module/Hello/HelloModule.php', '#[\\Sillbeam\\Module\\Attribute\\SillbeamModule] final class HelloModule {}');
            $source->php('src/Module/Hello/Live.php', '#[\\Symfony\\Component\\DependencyInjection\\Attribute\\AsAlias(id: "live", public: true)] final class Live {}');
            $source->write('config/bundles.php', '<?php return [\\Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle::class => ["all" => true], \\Sillbeam\\SillbeamBundle::class => ["all" => true]];');
            $source->write('config/packages/framework.yaml', 'framework: { secret: test }');
            $source->write('config/services.yaml', 'services: {}');
            $source->write('snapshot.php', <<<'PHP'
<?php
$loader = require $argv[1];
$loader->addPsr4($argv[2].'\\', __DIR__.'/src');
$kernel = new $argv[3]('dev', true);
try {
    $kernel->boot();
    $container = $kernel->getContainer();
    $modules = $container->get(\Sillbeam\Module\ModuleRegistryInterface::class);
    echo json_encode([count($modules->active()), $container->has('live')], JSON_THROW_ON_ERROR);
} finally { $kernel->shutdown(); }
PHP);
            $snapshot = static function () use ($source, $kernel): array {
                $log = $source->directory.'/snapshot.log';
                $process = proc_open([\PHP_BINARY, $source->directory.'/snapshot.php', \dirname(__DIR__, 2).'/vendor/autoload.php', $source->namespace, $kernel], [\STDIN, ['file', $log, 'w'], ['file', $log, 'a']], $pipes);
                self::assertIsResource($process);
                self::assertSame(0, proc_close($process), (string) file_get_contents($log));

                return json_decode((string) file_get_contents($log), true, flags: \JSON_THROW_ON_ERROR);
            };
            self::assertSame([1, true], $snapshot());
            self::assertSame([1, true], $snapshot());
            $configuration = 'config/packages/sillbeam.yaml';
            $source->write($configuration, 'sillbeam: { modules: { hello: { enabled: false } } }');
            // Filesystem timestamp granularity must not hide this mutation.
            touch($source->directory.'/'.$configuration, time() + 2);
            self::assertSame([0, false], $snapshot());
            $source->write($configuration, 'sillbeam: { modules: { hello: { enabled: true } } }');
            touch($source->directory.'/'.$configuration, time() + 2);
            self::assertSame([1, true], $snapshot());
            $source->write($configuration, 'sillbeam: { modules: { hello: { enabled: false } } }');
            touch($source->directory.'/'.$configuration, time() + 2);
            self::assertSame([0, false], $snapshot());
            unlink($source->directory.'/'.$configuration);
            touch($source->directory.'/config/packages', time() + 2);
            self::assertSame([1, true], $snapshot());
        } finally {
            $source->remove();
        }
    }
}
