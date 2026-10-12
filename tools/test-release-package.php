<?php

declare(strict_types=1);

// A test-only simulated version installs the real ZIP; no Git tag or release is created.
$repository = dirname(__DIR__);
$temporary = sys_get_temp_dir().'/sillbeam-release-package-'.bin2hex(random_bytes(6));
mkdir($temporary, 0777, true);

try {
    releaseRun(['composer', 'archive', '--format=zip', '--dir='.$temporary, '--file=sillbeam'], $repository);
    $package = json_decode((string) file_get_contents($repository.'/composer.json'), true, flags: \JSON_THROW_ON_ERROR);
    $application = $temporary.'/application';
    mkdir($application.'/config/packages', 0777, true);
    mkdir($application.'/src/Module/Hello/Controller', 0777, true);
    $metadata = array_intersect_key($package, array_flip(['name', 'description', 'type', 'license', 'require', 'autoload']));
    $metadata['version'] = '0.1.0';
    $metadata['dist'] = ['type' => 'zip', 'url' => 'file://'.$temporary.'/sillbeam.zip'];
    file_put_contents($application.'/composer.json', json_encode([
        'name' => 'sillbeam/manual-release-consumer', 'description' => 'Isolated manual release installation test.', 'type' => 'project', 'license' => 'MIT',
        'require' => ['php' => '^8.4', 'websign-dev/sillbeam' => '0.1.0'],
        'repositories' => [['type' => 'package', 'package' => $metadata]],
        'autoload' => ['psr-4' => ['App\\' => 'src/']],
    ], \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)."\n");
    releaseRun(['composer', 'install', '--no-interaction', '--no-progress', '--no-plugins', '--no-scripts', '--prefer-dist'], $application);
    releaseRun(['composer', 'validate', '--strict'], $application);
    releaseRun(['composer', 'check-platform-reqs'], $application);
    releaseRun(['composer', 'dump-autoload', '--optimize', '--strict-psr'], $application);

    $guide = (string) file_get_contents($repository.'/docs/guides/getting-started.md');
    preg_match_all('/```php\n(.*?)\n```/s', $guide, $php);
    foreach (['HelloModule', 'Greeting', 'HelloController'] as $class) {
        $examples = array_values(array_filter($php[1], static fn (string $code): bool => str_contains($code, 'class '.$class)));
        if (1 !== count($examples)) {
            throw new RuntimeException('Missing or ambiguous documented example: '.$class);
        }
        $path = 'HelloController' === $class ? 'Controller/'.$class : $class;
        file_put_contents($application.'/src/Module/Hello/'.$path.'.php', "<?php\n".$examples[0]."\n");
    }
    preg_match_all('/```yaml\n(.*?)\n```/s', $guide, $yaml);
    $services = array_values(array_filter($yaml[1], static fn (string $code): bool => str_contains($code, '_defaults:')));
    if (1 !== count($services)) {
        throw new RuntimeException('Missing documented native service import.');
    }
    file_put_contents($application.'/config/services.yaml', $services[0]."\n");
    file_put_contents($application.'/src/Legacy.php', '<?php namespace App; #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "legacy", public: true)] final class Legacy {}');
    file_put_contents($application.'/config/bundles.php', '<?php return [\Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ["all" => true], \Sillbeam\SillbeamBundle::class => ["all" => true]];');
    file_put_contents($application.'/config/packages/framework.yaml', "framework: { secret: manual-test, router: { utf8: true } }\n");
    file_put_contents($application.'/config/routes.yaml', "sillbeam: { resource: ., type: sillbeam }\n");
    file_put_contents($application.'/smoke.php', <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
if (class_exists('Doctrine\Bundle\DoctrineBundle\DoctrineBundle') || is_file(__DIR__.'/config/packages/sillbeam.yaml')) { throw new RuntimeException('Unexpected optional integration/configuration.'); }
$kernel = new App\Kernel($argv[1], 'dev' === $argv[1]);
try {
    $kernel->boot();
    if (!$kernel->getContainer()->has('legacy')) { throw new RuntimeException('Native service import failed.'); }
    $response = $kernel->handle(\Symfony\Component\HttpFoundation\Request::create('/hello'));
    if (200 !== $response->getStatusCode() || 'Hello from Sillbeam' !== $response->getContent()) { throw new RuntimeException('Documented Hello example failed.'); }
    $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
    $application->setAutoExit(false);
    foreach (['sillbeam:about', 'sillbeam:architecture'] as $name) {
        $tester = new \Symfony\Component\Console\Tester\CommandTester($application->find($name));
        if (0 !== $tester->execute([])) { throw new RuntimeException('Diagnostic command failed: '.$name); }
    }
} finally { $kernel->shutdown(); }
PHP);
    foreach (['symfony', 'sillbeam'] as $kernel) {
        $base = 'symfony' === $kernel ? '\\Symfony\\Component\\HttpKernel\\Kernel' : '\\Sillbeam\\Kernel\\SillbeamKernel';
        $trait = 'symfony' === $kernel ? 'use \\Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait;' : '';
        file_put_contents($application.'/src/Kernel.php', '<?php namespace App; final class Kernel extends '.$base.' { '.$trait.' public function getProjectDir(): string { return \\dirname(__DIR__); } public function getCacheDir(): string { return $this->getProjectDir()."/var/'.$kernel.'/".$this->environment; } }');
        foreach (['dev', 'prod'] as $environment) {
            for ($boot = 0; $boot < 2; ++$boot) {
                releaseRun([\PHP_BINARY, 'smoke.php', $environment], $application);
            }
        }
    }
    echo 'PASS real release ZIP / documented Hello / manual installation / both kernels / cold and warm dev+prod / no Doctrine'.\PHP_EOL;
} finally {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($temporary);
}

/** @param list<string> $command */
function releaseRun(array $command, string $directory): void
{
    $log = tempnam(sys_get_temp_dir(), 'sillbeam-release-run-');
    if (false === $log) {
        throw new RuntimeException('Cannot create command log.');
    }
    try {
        $process = proc_open($command, [\STDIN, ['file', $log, 'w'], ['file', $log, 'a']], $pipes, $directory);
        if (!is_resource($process) || 0 !== proc_close($process)) {
            throw new RuntimeException(implode(' ', $command)." failed:\n".file_get_contents($log));
        }
    } finally {
        unlink($log);
    }
}
