<?php

declare(strict_types=1);

require_once __DIR__.'/../tests/Support/PackageArchive.php';

// A test-only simulated version installs the real ZIP; no Git tag or release is created.
$repository = dirname(__DIR__);
$temporary = sys_get_temp_dir().'/sillbeam-release-package-'.bin2hex(random_bytes(6));
mkdir($temporary, 0777, true);

try {
    releaseRun(['composer', 'archive', '--format=zip', '--dir='.$temporary, '--file=sillbeam'], $repository);
    $inventory = Sillbeam\Core\Tests\Support\PackageArchive::inspect($temporary.'/sillbeam.zip', $repository);
    echo 'PASS ZIP contents: '.count($inventory['files']).' files, '.$inventory['source_files'].' identical runtime files, '.$inventory['markdown_links']." local documentation links\n";
    $package = json_decode((string) file_get_contents($repository.'/composer.json'), true, flags: \JSON_THROW_ON_ERROR);
    $application = $temporary.'/application';
    mkdir($application.'/config/packages', 0777, true);
    mkdir($application.'/src/Module/Hello/Controller', 0777, true);
    $metadata = array_intersect_key($package, array_flip(['name', 'description', 'type', 'license', 'require', 'autoload']));
    $metadata['version'] = '0.3.0';
    $require = ['php' => '^8.4', 'websign-dev/sillbeam-core' => '0.3.0'];
    $conflicts = [];
    if ($symfony = getenv('SILLBEAM_SYMFONY_VERSION')) {
        foreach ($package['require'] as $name => $constraint) {
            if (str_starts_with($name, 'symfony/')) {
                $require[$name] = $symfony;
            }
        }
        [$major, $minor] = array_map('intval', explode('.', $symfony));
        $lock = json_decode((string) file_get_contents($repository.'/composer.lock'), true, flags: \JSON_THROW_ON_ERROR);
        foreach ([...$lock['packages'], ...$lock['packages-dev']] as $dependency) {
            if (str_starts_with($dependency['name'], 'symfony/') && preg_match('/^v?[78]\./', $dependency['version'])) {
                $conflicts[$dependency['name']] = '<'.$major.'.'.$minor.' || >='.$major.'.'.($minor + 1);
            }
        }
    }
    $metadata['dist'] = ['type' => 'zip', 'url' => 'file://'.$temporary.'/sillbeam.zip'];
    file_put_contents($application.'/composer.json', json_encode([
        'name' => 'sillbeam/manual-release-consumer', 'description' => 'Isolated manual release installation test.', 'type' => 'project', 'license' => 'MIT',
        'require' => $require,
        'conflict' => (object) $conflicts,
        'repositories' => [['type' => 'package', 'package' => $metadata]],
        'autoload' => ['psr-4' => ['App\\' => 'src/']],
    ], \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)."\n");
    releaseRun(['composer', 'install', '--no-dev', '--no-interaction', '--no-progress', '--no-plugins', '--no-scripts', '--prefer-dist'], $application);
    releaseRun(['composer', 'validate', '--strict'], $application);
    releaseRun(['composer', 'check-platform-reqs'], $application);
    releaseRun(['composer', 'dump-autoload', '--optimize', '--strict-psr'], $application);

    $guide = (string) file_get_contents($application.'/vendor/websign-dev/sillbeam-core/docs/guides/getting-started.md');
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
    // Development files stay in application source even in a production-only install.
    mkdir($application.'/src/Project/Shop', 0777, true);
    file_put_contents($application.'/src/Project/Shop/ShopProject.php', '<?php namespace App\Project\Shop; #[\Sillbeam\Core\Project\Attribute\SillbeamProject(modules: [\App\Module\Hello\HelloModule::class])] final class ShopProject {}');
    foreach (['Module/Hello', 'Project/Shop'] as $area) {
        foreach (['Tests', 'Fixtures', 'Migrations', 'Nested/Tests', 'Nested/Fixtures', 'Nested/Migrations'] as $directory) {
            mkdir($application.'/src/'.$area.'/'.$directory, 0777, true);
            $namespace = 'App\\'.str_replace('/', '\\', $area.'/'.$directory);
            $parent = str_ends_with($directory, 'Tests') ? ' extends \PHPUnit\Framework\TestCase' : '';
            file_put_contents($application.'/src/'.$area.'/'.$directory.'/Helper.php', '<?php namespace '.$namespace.'; #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "development.'.str_replace('/', '.', $area.'/'.$directory).'", public: true)] final class Helper'.$parent.' {}');
        }
    }
    file_put_contents($application.'/src/Module/Hello/Fixtures/Manual.php', '<?php namespace App\Module\Hello\Fixtures; final class Manual { public function __construct(public string $value) {} }');
    file_put_contents($application.'/src/Module/Hello/DevOnly.php', '<?php namespace App\Module\Hello; #[\Symfony\Component\DependencyInjection\Attribute\When("dev")] #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "dev.only", public: true)] final class DevOnly {}');
    file_put_contents($application.'/src/Module/Hello/Excluded.php', '<?php namespace App\Module\Hello; #[\Symfony\Component\DependencyInjection\Attribute\Exclude] #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "excluded", public: true)] final class Excluded {}');
    file_put_contents($application.'/config/services.yaml', "    App\\Module\\Hello\\Greeting: { arguments: {} }\n    App\\Module\\Hello\\Fixtures\\Manual: { public: true, arguments: { \$value: explicit } }\n", \FILE_APPEND);

    file_put_contents($application.'/src/Legacy.php', '<?php namespace App; #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "legacy", public: true)] final class Legacy {}');
    file_put_contents($application.'/config/bundles.php', '<?php return [\Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ["all" => true], \Sillbeam\Core\SillbeamCoreBundle::class => ["all" => true]];');
    file_put_contents($application.'/config/packages/framework.yaml', "framework: { secret: manual-test, router: { utf8: true } }\n");
    file_put_contents($application.'/config/routes.yaml', "sillbeam: { resource: ., type: sillbeam }\n");
    file_put_contents($application.'/smoke.php', <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
if (class_exists('Doctrine\Bundle\DoctrineBundle\DoctrineBundle') || is_file(__DIR__.'/config/packages/sillbeam.yaml')) { throw new RuntimeException('Unexpected optional integration/configuration.'); }

if (class_exists('PHPUnit\Framework\TestCase')) { throw new RuntimeException('Consumer unexpectedly has development dependencies.'); }
$kernel = new App\Kernel($argv[1], 'dev' === $argv[1]);
try {
    if ('0.3.0' !== \Composer\InstalledVersions::getPrettyVersion('websign-dev/sillbeam-core')) { throw new RuntimeException('Wrong Core release version.'); }
    $kernel->boot();
    if (!$kernel->getContainer()->has('legacy')) { throw new RuntimeException('Native service import failed.'); }
    $container = $kernel->getContainer();
    foreach (['Module/Hello', 'Project/Shop'] as $area) {
        foreach (['Tests', 'Fixtures', 'Migrations', 'Nested/Tests', 'Nested/Fixtures', 'Nested/Migrations'] as $directory) {
            $class = 'development.'.str_replace('/', '.', $area.'/'.$directory);
            if ($container->has($class)) { throw new RuntimeException('Development service leaked: '.$class); }
        }
    }
    if ('explicit' !== $container->get('App\Module\Hello\Fixtures\Manual')->value) { throw new RuntimeException('Explicit fixture override failed.'); }
    if ($container->has('excluded') || $container->has('dev.only') !== ('dev' === $argv[1])) { throw new RuntimeException('Native Symfony attributes failed.'); }

    $response = $kernel->handle(\Symfony\Component\HttpFoundation\Request::create('/hello'));
    if (200 !== $response->getStatusCode() || 'Hello from Sillbeam' !== $response->getContent()) { throw new RuntimeException('Documented Hello example failed.'); }
    $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
    $application->setAutoExit(false);
    foreach (['sillbeam:about', 'sillbeam:architecture'] as $name) {
        $tester = new \Symfony\Component\Console\Tester\CommandTester($application->find($name));
        if (0 !== $tester->execute([])) { throw new RuntimeException('Diagnostic command failed: '.$name); }
    }
    $graph = new \Symfony\Component\Console\Tester\CommandTester($application->find('sillbeam:graph'));
    if (0 !== $graph->execute(['--output' => __DIR__.'/architecture-'.$argv[1].'.svg', '--force' => true]) || !is_file(__DIR__.'/architecture-'.$argv[1].'.svg')) { throw new RuntimeException('Packaged SVG export failed.'); }
    $inspect = new \Symfony\Component\Console\Tester\CommandTester($application->find('sillbeam:inspect:module'));
    if (0 !== $inspect->execute(['module' => 'hello']) || !str_contains($inspect->getDisplay(), 'Module: hello — Active')) { throw new RuntimeException('Packaged module inspection failed.'); }
    $doctor = new \Symfony\Component\Console\Tester\CommandTester($application->find('sillbeam:doctor'));
    $doctor->execute(['--format' => 'json']);
    $data = json_decode($doctor->getDisplay(), true, flags: \JSON_THROW_ON_ERROR);
    if (1 !== $data['schema_version']) { throw new RuntimeException('Packaged Doctor JSON failed.'); }
} finally { $kernel->shutdown(); }
PHP);
    foreach (['symfony', 'sillbeam'] as $kernel) {
        $base = 'symfony' === $kernel ? '\\Symfony\\Component\\HttpKernel\\Kernel' : '\\Sillbeam\\Core\\Kernel\\SillbeamKernel';
        $trait = 'symfony' === $kernel ? 'use \\Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait;' : '';
        file_put_contents($application.'/src/Kernel.php', '<?php namespace App; final class Kernel extends '.$base.' { '.$trait.' public function getProjectDir(): string { return \\dirname(__DIR__); } public function getCacheDir(): string { return $this->getProjectDir()."/var/'.$kernel.'/".$this->environment; } }');
        foreach (['dev', 'prod'] as $environment) {
            for ($boot = 0; $boot < 2; ++$boot) {
                releaseRun([\PHP_BINARY, 'smoke.php', $environment], $application);
            }
        }
    }
    echo 'PASS real release ZIP / documented Hello / manual installation / both kernels / cold and warm dev+prod / no Doctrine / no dev dependencies / development directory exclusions'.\PHP_EOL;
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
