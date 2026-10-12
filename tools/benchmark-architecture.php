<?php

declare(strict_types=1);

// Development tool: each size/environment runs in its own process and disposable workspace.
require dirname(__DIR__).'/vendor/autoload.php';

use Sillbeam\Architecture\ArchitectureCompiler;
use Sillbeam\Architecture\ArchitectureDiscovery;
use Sillbeam\Architecture\CodeDependencyAnalyzer;
use Sillbeam\Architecture\DiscoveryOptions;
use Sillbeam\Architecture\SourceInspector;
use Sillbeam\Tests\Support\SourceWorkspace;
use Sillbeam\Tests\Support\TestApplicationKernel;

if ('--worker' !== ($argv[1] ?? null)) {
    foreach ([10, 50, 100] as $modules) {
        foreach (['dev', 'prod'] as $environment) {
            $process = proc_open([\PHP_BINARY, __FILE__, '--worker', (string) $modules, $environment], [\STDIN, ['pipe', 'w'], \STDERR], $pipes);
            if (!is_resource($process)) {
                throw new RuntimeException('Cannot start benchmark worker.');
            }
            echo stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            if (0 !== proc_close($process)) {
                throw new RuntimeException('Benchmark worker failed.');
            }
        }
    }
    exit(0);
}

$count = (int) $argv[2];
$environment = $argv[3];
$source = new SourceWorkspace();
$kernel = new TestApplicationKernel($source, false, $environment, 'dev' === $environment);
$measure = static function (callable $operation): array {
    $start = hrtime(true);
    $result = $operation();

    return [$result, round((hrtime(true) - $start) / 1e6, 3)];
};

try {
    for ($module = 0; $module < $count; ++$module) {
        $area = 'src/Module/M'.$module;
        $previous = $source->namespace.'\\Module\\M'.($module - 1);
        $dependencies = 0 === $module ? '[]' : '[\\'.$previous.'\\Descriptor::class]';
        $source->php($area.'/Descriptor.php', '#[\Sillbeam\Module\Attribute\SillbeamModule(id: "m'.$module.'", dependencies: '.$dependencies.')] final class Descriptor {}');
        for ($class = 0; $class < 20; ++$class) {
            $body = 0 === $module ? '' : 'public function __construct(public \\'.$previous.'\\Service'.$class.' $previous) {}';
            $source->php($area.'/Service'.$class.'.php', 'final class Service'.$class.' { '.$body.' }');
        }
        $source->php($area.'/Controller/Endpoint.php', 'final class Endpoint { #[\Symfony\Component\Routing\Attribute\Route("/m'.$module.'", name: "m'.$module.'")] public function run(): \Symfony\Component\HttpFoundation\Response { return new \Symfony\Component\HttpFoundation\Response("ok"); } }');
    }
    $source->write('config/packages/framework.yaml', 'framework: { secret: benchmark, router: { utf8: true } }');
    $source->write('config/routes.yaml', 'sillbeam: { resource: ., type: sillbeam }');
    $source->write('config/services.yaml', "services:\n    _defaults: { autowire: true, autoconfigure: true }\n    '".$source->namespace."\\':\n        resource: '../src/'\n        exclude: '../src/{Module,Project}'\n");
    $options = new DiscoveryOptions($source->directory.'/src', $source->namespace.'\\');
    $inspector = new SourceInspector();
    [$files, $parse] = $measure(static fn () => $inspector->files($options));
    [, $indexWarm] = $measure(static fn () => $inspector->files($options));
    [$discovery, $discoveryTime] = $measure(static fn () => (new ArchitectureDiscovery($inspector))->discover($options));
    [$architecture, $compile] = $measure(static fn () => (new ArchitectureCompiler())->compile($discovery, []));
    [$references, $analysis] = $measure(static fn () => (new CodeDependencyAnalyzer($inspector))->analyze($discovery, $architecture, $options));
    [, $discoveryWarm] = $measure(static fn () => (new ArchitectureDiscovery($inspector))->discover($options));
    [, $analysisWarm] = $measure(static fn () => (new CodeDependencyAnalyzer($inspector))->analyze($discovery, $architecture, $options));
    [, $containerCold] = $measure(static fn () => $kernel->boot());
    [, $routesCold] = $measure(static fn () => $kernel->getContainer()->get('router')->getRouteCollection());
    $kernel->shutdown();
    [, $containerWarm] = $measure(static fn () => $kernel->boot());
    [, $routesWarm] = $measure(static fn () => $kernel->getContainer()->get('router')->getRouteCollection());
    echo json_encode([
        'php' => \PHP_VERSION, 'modules' => $count, 'environment' => $environment,
        'files' => count($files), 'references' => array_sum(array_map('count', $references)),
        'ms' => ['index_cold' => $parse, 'index_warm' => $indexWarm, 'discovery_after_index' => $discoveryTime, 'discovery_warm' => $discoveryWarm, 'compile' => $compile, 'analysis' => $analysis, 'analysis_warm' => $analysisWarm, 'container_cold' => $containerCold, 'container_warm' => $containerWarm, 'routes_cold' => $routesCold, 'routes_warm' => $routesWarm],
        'peak_mib' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES)."\n";
} finally {
    $kernel->shutdown();
    $source->remove();
}
