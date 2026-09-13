<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

$repositoryDirectory = dirname(__DIR__);
$recipeDirectory = $repositoryDirectory.'/flex-recipe/benjaminoeffinger/nexum/0.1';
$autoload = $repositoryDirectory.'/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(\STDERR, "Install the Nexum development dependencies before testing the recipe.\n");
    exit(1);
}

require $autoload;

$temporaryDirectory = sys_get_temp_dir().'/nexum-flex-recipe-'.bin2hex(random_bytes(8));
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
            'benjaminoeffinger/nexum' => [
                'manifest' => $manifest,
                'files' => $files,
                'ref' => hashDirectory($recipeDirectory),
            ],
        ],
    ];
    writeJson($endpointDirectory.'/benjaminoeffinger.nexum.0.1.json', $recipeResponse);

    $endpointPort = availablePort();
    $endpointBaseUrl = sprintf('http://127.0.0.1:%d', $endpointPort);
    $endpoint = [
        'recipes' => [
            'benjaminoeffinger/nexum' => ['0.1'],
        ],
        'branch' => 'main',
        'is_contrib' => true,
        '_links' => [
            'repository' => 'local/nexum-flex-recipe',
            'origin_template' => '{package}:{version}@local/nexum-flex-recipe:main',
            'recipe_template' => $endpointBaseUrl.'/{package_dotted}.{version}.json',
        ],
    ];
    writeJson($endpointDirectory.'/index.json', $endpoint);

    [$endpointServer, $endpointPipes] = startEndpointServer($endpointDirectory, $endpointPort);
    waitForEndpoint($endpointBaseUrl.'/index.json');

    writeJson($applicationDirectory.'/composer.json', [
        'name' => 'nexum/flex-recipe-test',
        'type' => 'project',
        'repositories' => [[
            'type' => 'path',
            'url' => $repositoryDirectory,
            'options' => ['symlink' => false],
        ]],
        'require' => [
            'benjaminoeffinger/nexum' => 'dev-main',
            'symfony/flex' => '^2.11',
        ],
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
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
    ]);

    runComposer($applicationDirectory);

    $bundles = require $applicationDirectory.'/config/bundles.php';
    assertSame(['all' => true], $bundles['Nexum\\NexumBundle'] ?? null, 'NexumBundle was not registered.');
    $nexumBundles = array_values(array_filter(
        array_keys($bundles),
        static fn (string $bundle): bool => str_starts_with($bundle, 'Nexum\\'),
    ));
    assertSame(['Nexum\\NexumBundle'], $nexumBundles, 'The recipe must register only the central NexumBundle.');

    $nexumConfig = Yaml::parseFile($applicationDirectory.'/config/packages/nexum.yaml');
    assertSame(false, $nexumConfig['nexum']['doctrine']['enabled'] ?? null, 'Doctrine must be disabled by the recipe.');
    assertSame(false, $nexumConfig['nexum']['api']['enabled'] ?? null, 'API must be disabled by the recipe.');
    assertSame(false, $nexumConfig['nexum']['security']['enabled'] ?? null, 'Security must be disabled by the recipe.');

    $routes = Yaml::parseFile($applicationDirectory.'/config/routes/nexum.yaml');
    assertSame('nexum', $routes['nexum']['type'] ?? null, 'The Nexum route loader was not installed.');

    $lock = decodeJson($applicationDirectory.'/symfony.lock');
    assertSame('0.1', $lock['benjaminoeffinger/nexum']['recipe']['version'] ?? null, 'The recipe is missing from symfony.lock.');
    $installedFiles = $lock['benjaminoeffinger/nexum']['files'] ?? [];
    sort($installedFiles);
    assertSame([
        'config/packages/nexum.yaml',
        'config/routes/nexum.yaml',
    ], $installedFiles, 'symfony.lock does not track exactly the two Nexum recipe files.');

    fwrite(\STDOUT, "Nexum Flex recipe installation passed.\n");
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
