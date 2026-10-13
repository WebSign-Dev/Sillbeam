<?php

declare(strict_types=1);

// Each installation has its own Composer resolution and autoloader; no root vendor is loaded.
$repository = dirname(__DIR__);
$temporary = sys_get_temp_dir().'/sillbeam-consumers-'.bin2hex(random_bytes(8));
$package = json_decode((string) file_get_contents($repository.'/composer.json'), true, flags: \JSON_THROW_ON_ERROR);
$lock = json_decode((string) file_get_contents($repository.'/composer.lock'), true, flags: \JSON_THROW_ON_ERROR);
$symfony = getenv('SILLBEAM_SYMFONY_VERSION') ?: null;
$conflicts = [];
if (null !== $symfony) {
    [$major, $minor] = array_map('intval', explode('.', $symfony));
    foreach ([...$lock['packages'], ...$lock['packages-dev']] as $dependency) {
        if (str_starts_with($dependency['name'], 'symfony/') && preg_match('/^v?[78]\./', $dependency['version'])) {
            $conflicts[$dependency['name']] = '<'.$major.'.'.$minor.' || >='.$major.'.'.($minor + 1);
        }
    }
}
$scenarios = [
    'symfony-only' => 'SymfonyOnlyApp',
    'modules-only' => 'ModulesOnlyApp',
    'project-only' => 'ProjectOnlyApp',
    'single-project' => 'SingleProjectApp',
    'multi-project' => 'ProjectApp',
    'hybrid' => 'HybridApp',
];
mkdir($temporary, 0777, true);

try {
    // Stage the actual runtime package, independent of checkout-only files and virtual mounts.
    $packageDirectory = $temporary.'/package';
    mkdir($packageDirectory, 0777, true);
    consumerCopy($repository.'/src', $packageDirectory.'/src');
    foreach (['composer.json', 'LICENSE', 'README.md'] as $file) {
        copy($repository.'/'.$file, $packageDirectory.'/'.$file);
    }
    foreach ($scenarios as $scenario => $namespace) {
        foreach ([false, true] as $doctrine) {
            $directory = $temporary.'/'.$scenario.($doctrine ? '-doctrine' : '-plain');
            mkdir($directory, 0777, true);
            consumerCopy($repository.'/apps/'.$scenario.'/src', $directory.'/src');
            consumerCopy($repository.'/apps/'.$scenario.'/config', $directory.'/config');
            @unlink($directory.'/config/reference.php');
            $require = ['php' => '^8.4', 'websign-dev/sillbeam' => '0.1.0'];
            foreach ($package['require'] as $name => $constraint) {
                if (str_starts_with($name, 'symfony/')) {
                    $require[$name] = $symfony ?? $constraint;
                }
            }
            if ($doctrine) {
                $require['doctrine/doctrine-bundle'] = '^3.3';
                $require['doctrine/orm'] = '^3.6';
                $require['symfony/doctrine-bridge'] = $symfony ?? '^7.4 || ^8.0';
            }
            consumerJson($directory.'/composer.json', [
                'name' => 'sillbeam/consumer-'.$scenario,
                'description' => 'Independent Sillbeam installation smoke test.',
                'type' => 'project',
                'license' => 'MIT',
                'require' => $require,
                'conflict' => (object) $conflicts,
                'repositories' => [[
                    'type' => 'path', 'url' => $packageDirectory,
                    'options' => ['symlink' => false, 'versions' => ['websign-dev/sillbeam' => '0.1.0']],
                ]],
                'autoload' => ['psr-4' => [$namespace.'\\' => 'src/']],
            ]);
            $bundles = [
                'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle' => ['all' => true],
                'Sillbeam\\SillbeamBundle' => ['all' => true],
            ];
            if ($doctrine) {
                $bundles['Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle'] = ['all' => true];
            }
            file_put_contents($directory.'/config/bundles.php', '<?php return '.var_export($bundles, true).';');
            // Exercise the same scenarios with and without the optional integration.
            file_put_contents($directory.'/config/packages/zz-consumer.yaml', "sillbeam:\n    doctrine: ".($doctrine ? 'true' : 'false')."\n");
            if (!$doctrine) {
                @unlink($directory.'/config/packages/doctrine.yaml');
            } else {
                mkdir($directory.'/src/Entity', 0777, true);
                file_put_contents($directory.'/src/Entity/ConsumerRecord.php', '<?php namespace '.$namespace.'\\Entity; #[\\Doctrine\\ORM\\Mapping\\Entity] final class ConsumerRecord { #[\\Doctrine\\ORM\\Mapping\\Id] #[\\Doctrine\\ORM\\Mapping\\Column(type: "integer")] public int $id = 1; }');
                file_put_contents($directory.'/config/packages/doctrine.yaml', "doctrine:\n    dbal: { url: 'sqlite:///:memory:' }\n    orm:\n        auto_mapping: false\n        mappings:\n            Native:\n                type: attribute\n                is_bundle: false\n                dir: '%kernel.project_dir%/src/Entity'\n                prefix: '".$namespace."\\Entity'\n");
                if ('symfony-only' !== $scenario && 'project-only' !== $scenario) {
                    $area = match ($scenario) {
                        'modules-only' => 'Module/Inventory',
                        'single-project' => 'Project/Portal/Module/Dashboard',
                        'multi-project' => 'Project/Storefront/Module/Checkout',
                        'hybrid' => 'Module/Common',
                    };
                    $entityDirectory = $directory.'/src/'.$area.'/Entity';
                    if (!is_dir($entityDirectory)) {
                        mkdir($entityDirectory, 0777, true);
                    }
                    file_put_contents($entityDirectory.'/MappedRecord.php', str_replace([$namespace.'\\Entity', 'ConsumerRecord'], [$namespace.'\\'.str_replace('/', '\\', $area).'\\Entity', 'MappedRecord'], (string) file_get_contents($directory.'/src/Entity/ConsumerRecord.php')));
                }
            }
            consumerRun(['composer', 'install', '--no-interaction', '--no-progress', '--no-plugins', '--no-scripts', '--prefer-dist'], $directory);
            consumerRun(['composer', 'validate', '--strict'], $directory);
            consumerRun(['composer', 'check-platform-reqs'], $directory);
            consumerRun(['composer', 'dump-autoload', '--optimize', '--strict-psr'], $directory);
            copy(__DIR__.'/consumer-smoke.php', $directory.'/smoke.php');
            foreach (['symfony', 'sillbeam'] as $kernel) {
                $base = 'sillbeam' === $kernel ? '\\Sillbeam\\Kernel\\SillbeamKernel' : '\\Symfony\\Component\\HttpKernel\\Kernel';
                $trait = 'symfony' === $kernel ? 'use \\Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait;' : '';
                file_put_contents($directory.'/src/Kernel.php', '<?php namespace '.$namespace.'; final class Kernel extends '.$base.' { '.$trait.' public function getProjectDir(): string { return \\dirname(__DIR__); } public function getCacheDir(): string { return $this->getProjectDir()."/var/'.$kernel.'/".$this->environment; } public function getLogDir(): string { return $this->getProjectDir()."/var/log"; } }');
                consumerRun([\PHP_BINARY, 'smoke.php', $scenario, $namespace, $doctrine ? '1' : '0'], $directory);
            }
            if ('modules-only' === $scenario) {
                file_put_contents($directory.'/src/Module/Inventory/InvalidReference.php', '<?php namespace '.$namespace.'\\Module\\Inventory; final class InvalidReference { public function target(): string { return \\'.$namespace.'\\Module\\Archived\\Controller\\ArchivedController::class; } }');
                consumerRun([\PHP_BINARY, 'smoke.php', $scenario, $namespace, $doctrine ? '1' : '0', 'invalid'], $directory);
            }
            $installed = json_decode((string) file_get_contents($directory.'/composer.lock'), true, flags: \JSON_THROW_ON_ERROR);
            $versions = array_column($installed['packages'], 'version', 'name');
            printf("PASS PHP %s / Symfony %s / %s / %s / both kernels\n", \PHP_VERSION, $versions['symfony/framework-bundle'], $scenario, $doctrine ? 'Doctrine '.$versions['doctrine/doctrine-bundle'].' / ORM '.$versions['doctrine/orm'] : 'no Doctrine');
        }
    }
} finally {
    consumerRemove($temporary);
}

/** @param list<string> $command */
function consumerRun(array $command, string $directory): void
{
    $log = $directory.'/command.log';
    $process = proc_open($command, [\STDIN, ['file', $log, 'w'], ['file', $log, 'a']], $pipes, $directory);
    if (!is_resource($process) || 0 !== proc_close($process)) {
        throw new RuntimeException(implode(' ', $command).' failed in '.$directory."\n".file_get_contents($log));
    }
}

/** @param array<string, mixed> $data */
function consumerJson(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)."\n");
}

function consumerCopy(string $source, string $target): void
{
    mkdir($target, 0777, true);
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $entry) {
        $path = $target.substr($entry->getPathname(), strlen($source));
        if ($entry->isDir()) {
            mkdir($path, 0777, true);
        } else {
            copy($entry->getPathname(), $path);
        }
    }
}

function consumerRemove(string $directory): void
{
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($directory);
}
