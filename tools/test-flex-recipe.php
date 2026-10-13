<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

$repositoryDirectory = dirname(__DIR__);
$recipeDirectory = $repositoryDirectory.'/flex-recipe/websign-dev/sillbeam/0.1';
$autoload = $repositoryDirectory.'/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(\STDERR, "Install the Sillbeam development dependencies before testing the recipe.\n");
    exit(1);
}

require $autoload;

$withDoctrine = '1' === getenv('SILLBEAM_TEST_DOCTRINE');
$symfony = getenv('SILLBEAM_SYMFONY_VERSION') ?: null;
$requirements = ['websign-dev/sillbeam' => '0.1.0', 'symfony/flex' => '^2.11', 'symfony/framework-bundle' => $symfony ?? '^7.4 || ^8.0', 'symfony/console' => $symfony ?? '^7.4 || ^8.0', 'symfony/yaml' => $symfony ?? '^7.4 || ^8.0'];
$conflicts = [];
if ($withDoctrine) {
    $requirements['doctrine/doctrine-bundle'] = '^3.3';
    $requirements['doctrine/orm'] = '^3.6';
    $requirements['symfony/doctrine-bridge'] = $symfony ?? '^7.4 || ^8.0';
}
if (null !== $symfony) {
    [$major, $minor] = array_map('intval', explode('.', $symfony));
    $lock = decodeJson($repositoryDirectory.'/composer.lock');
    foreach ([...$lock['packages'], ...$lock['packages-dev']] as $dependency) {
        if (str_starts_with($dependency['name'], 'symfony/') && preg_match('/^v?[78]\./', $dependency['version'])) {
            $conflicts[$dependency['name']] = '<'.$major.'.'.$minor.' || >='.$major.'.'.($minor + 1);
        }
    }
}

$temporaryDirectory = sys_get_temp_dir().'/sillbeam-flex-recipe-'.bin2hex(random_bytes(8));
$endpointDirectory = $temporaryDirectory.'/endpoint';
$applicationDirectory = $temporaryDirectory.'/application';
$endpointServer = null;
$endpointPipes = [];

mkdir($endpointDirectory, 0777, true);
mkdir($applicationDirectory, 0777, true);

try {
    $manifest = decodeJson($recipeDirectory.'/manifest.json');
    $files = [];

    foreach (recipeFiles($recipeDirectory.'/config') as $file) {
        $relativePath = substr($file, strlen($recipeDirectory) + 1);
        $contents = file($file, \FILE_IGNORE_NEW_LINES);

        if (false === $contents) {
            throw new RuntimeException(sprintf('Unable to read recipe file "%s".', $file));
        }

        $files[$relativePath] = [
            'contents' => $contents,
            'executable' => false,
        ];
    }

    $recipeResponse = [
        'manifests' => [
            'websign-dev/sillbeam' => [
                'manifest' => $manifest,
                'files' => $files,
                'ref' => hashDirectory($recipeDirectory),
            ],
        ],
    ];
    writeJson($endpointDirectory.'/websign-dev.sillbeam.0.1.json', $recipeResponse);

    $endpointPort = availablePort();
    $endpointBaseUrl = sprintf('http://127.0.0.1:%d', $endpointPort);
    $endpoint = [
        'recipes' => [
            'websign-dev/sillbeam' => ['0.1'],
        ],
        'branch' => 'main',
        'is_contrib' => true,
        '_links' => [
            'repository' => 'local/sillbeam-flex-recipe',
            'origin_template' => '{package}:{version}@local/sillbeam-flex-recipe:main',
            'recipe_template' => $endpointBaseUrl.'/{package_dotted}.{version}.json',
        ],
    ];
    writeJson($endpointDirectory.'/index.json', $endpoint);

    [$endpointServer, $endpointPipes] = startEndpointServer($endpointDirectory, $endpointPort);
    waitForEndpoint($endpointBaseUrl.'/index.json');

    $packageDirectory = $temporaryDirectory.'/package';
    foreach ([$repositoryDirectory.'/composer.json', $repositoryDirectory.'/LICENSE', $repositoryDirectory.'/README.md', ...recipeFiles($repositoryDirectory.'/src')] as $file) {
        $target = $packageDirectory.substr($file, strlen($repositoryDirectory));
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }
        copy($file, $target);
    }

    writeJson($applicationDirectory.'/composer.json', [
        'name' => 'sillbeam/flex-recipe-test',
        'type' => 'project',
        'repositories' => [[
            'type' => 'path',
            'url' => $packageDirectory,
            'options' => ['symlink' => false, 'versions' => ['websign-dev/sillbeam' => '0.1.0']],
        ]],
        'require' => $requirements,
        'conflict' => (object) $conflicts,
        'autoload' => ['psr-4' => ['App\\' => 'src/']],
        'config' => [
            'allow-plugins' => ['symfony/flex' => true],
            'secure-http' => false,
            'sort-packages' => true,
        ],
        'extra' => [
            'symfony' => [
                'allow-contrib' => true,
                'endpoint' => [$endpointBaseUrl.'/index.json'],
            ],
        ],
        'prefer-stable' => true,
    ]);

    runComposer($applicationDirectory);

    $bundles = require $applicationDirectory.'/config/bundles.php';
    assertSame(['all' => true], $bundles['Sillbeam\\SillbeamBundle'] ?? null, 'SillbeamBundle was not registered.');
    $sillbeamBundles = array_values(array_filter(
        array_keys($bundles),
        static fn (string $bundle): bool => str_starts_with($bundle, 'Sillbeam\\'),
    ));
    assertSame(['Sillbeam\\SillbeamBundle'], $sillbeamBundles, 'The recipe must register only the central SillbeamBundle.');

    assertSame(false, is_file($applicationDirectory.'/config/packages/sillbeam.yaml'), 'The recipe must not install an empty package configuration.');

    $routes = Yaml::parseFile($applicationDirectory.'/config/routes/sillbeam.yaml');
    assertSame('sillbeam', $routes['sillbeam']['type'] ?? null, 'The Sillbeam route loader was not installed.');

    $lock = decodeJson($applicationDirectory.'/symfony.lock');
    assertSame('0.1', $lock['websign-dev/sillbeam']['recipe']['version'] ?? null, 'The recipe is missing from symfony.lock.');
    $installedFiles = $lock['websign-dev/sillbeam']['files'] ?? [];
    sort($installedFiles);
    assertSame([
        'config/routes/sillbeam.yaml',
    ], $installedFiles, 'symfony.lock does not track only the Sillbeam route import.');

    bootConsumer($applicationDirectory, $withDoctrine);
    uninstallRecipe($applicationDirectory);
    fwrite(\STDOUT, 'Sillbeam Flex recipe installation, both consumer kernels and removal passed ('.($withDoctrine ? 'with Doctrine' : 'without Doctrine').").\n");
} finally {
    if (is_resource($endpointServer)) {
        proc_terminate($endpointServer);
        proc_close($endpointServer);
    }
    foreach ($endpointPipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }
    removeDirectory($temporaryDirectory);
}

/** @return list<string> */
function recipeFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

/** @return array<string, mixed> */
function decodeJson(string $file): array
{
    $contents = file_get_contents($file);

    if (false === $contents) {
        throw new RuntimeException(sprintf('Unable to read "%s".', $file));
    }

    return json_decode($contents, true, flags: \JSON_THROW_ON_ERROR);
}

/** @param array<string, mixed> $data */
function writeJson(string $file, array $data): void
{
    $json = json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);

    if (false === file_put_contents($file, $json."\n")) {
        throw new RuntimeException(sprintf('Unable to write "%s".', $file));
    }
}

function hashDirectory(string $directory): string
{
    $hash = hash_init('sha1');

    foreach (recipeFiles($directory) as $file) {
        hash_update($hash, substr($file, strlen($directory) + 1));
        hash_update_file($hash, $file);
    }

    return hash_final($hash);
}

function runComposer(string $workingDirectory): void
{
    $process = proc_open([
        'composer',
        'install',
        '--no-interaction',
        '--no-progress',
        '--no-scripts',
        '--prefer-dist',
    ], [\STDIN, \STDOUT, \STDERR], $pipes, $workingDirectory);

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start Composer.');
    }

    $exitCode = proc_close($process);

    if (0 !== $exitCode) {
        throw new RuntimeException(sprintf('Composer exited with status %d.', $exitCode));
    }
}

/** Boots the installed consumer in another process without the repository development autoloader. */
function bootConsumer(string $directory, bool $withDoctrine): void
{
    if (!is_dir($directory.'/config/packages')) {
        mkdir($directory.'/config/packages', 0777, true);
    }
    $bundles = require $directory.'/config/bundles.php';
    $bundles[Symfony\Bundle\FrameworkBundle\FrameworkBundle::class] = ['all' => true];
    if ($withDoctrine) {
        $bundles[Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class] = ['all' => true];
        file_put_contents($directory.'/config/packages/doctrine.yaml', "doctrine:\n    dbal: { url: 'sqlite:///:memory:' }\n    orm: { auto_mapping: false }\n");
    }
    file_put_contents($directory.'/config/bundles.php', '<?php return '.var_export($bundles, true).';');
    file_put_contents($directory.'/config/packages/framework.yaml', "framework:\n    secret: smoke\n    router: { utf8: true }\n");
    file_put_contents($directory.'/config/services.yaml', <<<'YAML'
services:
    _defaults: { autowire: true, autoconfigure: true }
    App\:
        resource: '../src/'
        exclude: '../src/{DependencyInjection,Entity,Kernel.php,Module,Project}'
YAML);
    mkdir($directory.'/src/Module/Off', 0777, true);
    file_put_contents($directory.'/src/Module/Off/OffModule.php', '<?php namespace App\Module\Off; #[\Sillbeam\Module\Attribute\SillbeamModule] final class OffModule {}');
    file_put_contents($directory.'/src/Module/Off/Leak.php', '<?php namespace App\Module\Off; #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "off.leak", public: true)] final class Leak {}');
    file_put_contents($directory.'/src/Legacy.php', '<?php namespace App; #[\Symfony\Component\DependencyInjection\Attribute\AsAlias(id: "legacy", public: true)] final class Legacy {}');
    mkdir($directory.'/src/Module/Hello/Controller', 0777, true);
    file_put_contents($directory.'/src/Module/Hello/HelloModule.php', '<?php namespace App\Module\Hello; #[\Sillbeam\Module\Attribute\SillbeamModule] final class HelloModule {}');
    file_put_contents($directory.'/src/Module/Hello/Controller/HelloController.php', '<?php namespace App\Module\Hello\Controller; final class HelloController { #[\Symfony\Component\Routing\Attribute\Route("/hello", name: "hello")] public function hello(): \Symfony\Component\HttpFoundation\Response { return new \Symfony\Component\HttpFoundation\Response("hello"); } }');
    if ($withDoctrine) {
        mkdir($directory.'/src/Module/Hello/Entity', 0777, true);
        file_put_contents($directory.'/src/Module/Hello/Entity/Record.php', '<?php namespace App\Module\Hello\Entity; #[\Doctrine\ORM\Mapping\Entity] final class Record { #[\Doctrine\ORM\Mapping\Id] #[\Doctrine\ORM\Mapping\Column(type: "integer")] public int $id = 1; }');
    }
    file_put_contents($directory.'/src/Kernel.php', <<<'PHP'
<?php
namespace App;
final class Kernel extends \Symfony\Component\HttpKernel\Kernel
{
    use \Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
    public function getProjectDir(): string { return \dirname(__DIR__); }
}
PHP);
    file_put_contents($directory.'/smoke.php', <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
if (class_exists('Doctrine\Bundle\DoctrineBundle\DoctrineBundle') !== ('1' === $argv[1])) { throw new \RuntimeException('Unexpected Doctrine installation.'); }
$zero = 'zero' === ($argv[3] ?? null);
if ($zero && is_file(__DIR__.'/config/packages/sillbeam.yaml')) { throw new \RuntimeException('Zero-config installation requires no package configuration.'); }
$kernel = new App\Kernel($argv[2], 'dev' === $argv[2]);
$kernel->boot();
try {
    if (!$kernel->getContainer()->has('legacy') || $kernel->getContainer()->has('off.leak') !== $zero) { throw new \RuntimeException('Skeleton service import boundaries failed.'); }
    $registry = $kernel->getContainer()->get(\Sillbeam\Module\ModuleRegistryInterface::class);
    if (($zero ? 2 : 1) !== \count($registry->active())) { throw new \RuntimeException('Missing active module.'); }
    $response = $kernel->handle(\Symfony\Component\HttpFoundation\Request::create('/hello'));
    if (200 !== $response->getStatusCode() || 'hello' !== $response->getContent()) { throw new \RuntimeException('Module route failed.'); }
    if ('1' === $argv[1]) {
        $manager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        if (1 !== count($manager->getMetadataFactory()->getAllMetadata())) { throw new \RuntimeException('Recipe consumer mapping failed.'); }
    }
} finally { $kernel->shutdown(); }
PHP);
    $process = proc_open([\PHP_BINARY, 'smoke.php', $withDoctrine ? '1' : '0', 'dev', 'zero'], [\STDIN, \STDOUT, \STDERR], $pipes, $directory);
    if (!is_resource($process) || 0 !== proc_close($process)) {
        throw new RuntimeException('Consumer boot without package configuration failed.');
    }
    removeDirectory($directory.'/var/cache');
    file_put_contents($directory.'/config/packages/sillbeam.yaml', "sillbeam: { modules: { off: { enabled: false } } }\n");
    foreach (['symfony', 'sillbeam'] as $kernel) {
        if ('sillbeam' === $kernel) {
            file_put_contents($directory.'/src/Kernel.php', '<?php namespace App; final class Kernel extends \\Sillbeam\\Kernel\\SillbeamKernel { public function getProjectDir(): string { return \\dirname(__DIR__); } }');
        }
        foreach (['dev', 'prod'] as $environment) {
            for ($boot = 0; $boot < 2; ++$boot) {
                $process = proc_open([\PHP_BINARY, 'smoke.php', $withDoctrine ? '1' : '0', $environment], [\STDIN, \STDOUT, \STDERR], $pipes, $directory);
                if (!is_resource($process) || 0 !== proc_close($process)) {
                    throw new RuntimeException('Consumer boot failed: '.$kernel.'/'.$environment);
                }
            }
        }
        // A different Kernel needs its own freshly built caches.
        removeDirectory($directory.'/var/cache');
    }
}

/** Check that uninstall owns only the recipe files and bundle registration. */
function uninstallRecipe(string $directory): void
{
    $services = file_get_contents($directory.'/config/services.yaml');
    $configuration = file_get_contents($directory.'/config/packages/sillbeam.yaml');
    $process = proc_open(['composer', 'remove', 'websign-dev/sillbeam', '--no-interaction', '--no-progress', '--no-scripts'], [\STDIN, \STDOUT, \STDERR], $pipes, $directory);
    if (!is_resource($process) || 0 !== proc_close($process)) {
        throw new RuntimeException('Recipe removal failed.');
    }
    $bundles = require $directory.'/config/bundles.php';
    assertSame(false, isset($bundles['Sillbeam\\SillbeamBundle']), 'Recipe bundle registration was not removed.');
    assertSame(true, isset($bundles['Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle']), 'Native bundle registration must survive.');
    assertSame(false, is_file($directory.'/config/routes/sillbeam.yaml'), 'Recipe route import was not removed.');
    assertSame($services, file_get_contents($directory.'/config/services.yaml'), 'Recipe removal changed native services.');
    assertSame($configuration, file_get_contents($directory.'/config/packages/sillbeam.yaml'), 'Recipe removal deleted user configuration.');
    $lock = decodeJson($directory.'/symfony.lock');
    assertSame(false, isset($lock['websign-dev/sillbeam']), 'Recipe lock entry was not removed.');
    // Application-owned options and an optional custom Kernel need explicit cleanup.
    // Preserve the configuration contents while moving them out of Symfony's imports.
    rename($directory.'/config/packages/sillbeam.yaml', $directory.'/config/sillbeam.yaml.uninstalled');
    file_put_contents($directory.'/src/Kernel.php', '<?php namespace App; final class Kernel extends \\Symfony\\Component\\HttpKernel\\Kernel { use \\Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait; public function getProjectDir(): string { return \\dirname(__DIR__); } }');
    file_put_contents($directory.'/removed.php', <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$kernel = new App\Kernel('prod', false);
try {
    $kernel->boot();
    if (!$kernel->getContainer()->has('legacy') || class_exists('Sillbeam\\SillbeamBundle')) { throw new RuntimeException('Native consumer failed after removal and application cleanup.'); }
} finally { $kernel->shutdown(); }
PHP);
    $process = proc_open([\PHP_BINARY, 'removed.php'], [\STDIN, \STDOUT, \STDERR], $pipes, $directory);
    if (!is_resource($process) || 0 !== proc_close($process)) {
        throw new RuntimeException('Native consumer boot after removal failed.');
    }
}

function availablePort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);

    if (false === $socket) {
        throw new RuntimeException(sprintf('Unable to allocate endpoint port: %s (%d).', $errorMessage, $errorCode));
    }

    $address = stream_socket_get_name($socket, false);
    fclose($socket);

    if (false === $address || !preg_match('/:(\d+)$/', $address, $matches)) {
        throw new RuntimeException('Unable to determine the endpoint port.');
    }

    return (int) $matches[1];
}

/** @return array{resource, array<int, resource>} */
function startEndpointServer(string $documentRoot, int $port): array
{
    $pipes = [];
    $process = proc_open(
        [\PHP_BINARY, '-S', sprintf('127.0.0.1:%d', $port), '-t', $documentRoot],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the local Flex endpoint.');
    }

    fclose($pipes[0]);
    unset($pipes[0]);

    return [$process, $pipes];
}

function waitForEndpoint(string $url): void
{
    $deadline = microtime(true) + 5;

    do {
        $contents = @file_get_contents($url);
        if (false !== $contents) {
            return;
        }
        usleep(50_000);
    } while (microtime(true) < $deadline);

    throw new RuntimeException('The local Flex endpoint did not become available.');
}

function assertSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

function removeDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($directory);
}
