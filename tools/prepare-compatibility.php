<?php

declare(strict_types=1);

// Run only in a disposable checkout: this selects dependencies for one matrix job.
$version = getenv('SILLBEAM_SYMFONY_VERSION') ?: throw new RuntimeException('Set SILLBEAM_SYMFONY_VERSION to 7.4.* or 8.1.*.');
$config = json_decode((string) file_get_contents('composer.json'), true, flags: \JSON_THROW_ON_ERROR);
$lock = json_decode((string) file_get_contents('composer.lock'), true, flags: \JSON_THROW_ON_ERROR);
foreach ($config['require'] as $package => &$constraint) {
    if (str_starts_with($package, 'symfony/')) {
        $constraint = $version;
    }
}
unset($constraint);
// Contracts, polyfills and Flex have independent version numbers.
foreach ([...$lock['packages'], ...$lock['packages-dev']] as $package) {
    if (str_starts_with($package['name'], 'symfony/') && preg_match('/^v?[78]\./', $package['version']) && !isset($config['require'][$package['name']])) {
        $config['require-dev'][$package['name']] = $version;
    }
}
$config['require-dev']['symfony/doctrine-bridge'] = $version;
file_put_contents('composer.json', json_encode($config, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)."\n");
