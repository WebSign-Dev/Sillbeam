<?php

declare(strict_types=1);

use Sillbeam\Documentation\JsonExport;

require __DIR__.'/phpdoc-json/JsonExport.php';

// Run phpDocumentor itself in its isolated PHAR; never load it into the runtime autoloader.
$root = dirname(__DIR__);
chdir($root);
$temporary = $root.'/.phpdoc/json-export-'.bin2hex(random_bytes(8));
$output = $root.'/build/docs/api/sillbeam-core.en.json';
$pending = null;
$exitCode = 0;
try {
    if (!is_dir($temporary) && !mkdir($temporary, 0777, true)) {
        throw new RuntimeException('Cannot create phpDocumentor staging directory.');
    }
    $command = [\PHP_BINARY, $root.'/vendor/bin/phpdoc', '--config=phpdoc.dist.xml', '--template=tools/phpdoc-json', '--target='.$temporary, '--cache-folder=.phpdoc/json-cache', '--sourcecode', '--no-extensions'];
    $process = proc_open($command, [\STDIN, \STDOUT, \STDERR], $pipes, $root);
    if (!is_resource($process) || 0 !== proc_close($process)) {
        throw new RuntimeException('phpDocumentor JSON transformation failed.');
    }
    $data = json_decode((string) file_get_contents($temporary.'/descriptors.json'), true, flags: \JSON_THROW_ON_ERROR);
    $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: \JSON_THROW_ON_ERROR);
    $config = simplexml_load_file($root.'/phpdoc.dist.xml');
    $versions = false === $config ? false : $config->xpath('//*[local-name()="version"]');
    if (!is_array($versions) || 1 !== count($versions) || !isset($versions[0]) || '' === (string) $versions[0]['number']) {
        throw new RuntimeException('Expected one version number in phpdoc.dist.xml.');
    }
    $json = (new JsonExport())->encode($data, $composer['name'], (string) $versions[0]['number']);
    if (!is_dir(dirname($output)) && !mkdir(dirname($output), 0777, true)) {
        throw new RuntimeException('Cannot create JSON output directory.');
    }
    $pending = tempnam(dirname($output), '.sillbeam-json-');
    if (false === $pending || strlen($json) !== file_put_contents($pending, $json) || !rename($pending, $output)) {
        throw new RuntimeException('Cannot atomically write '.$output.'.');
    }
    echo 'Exported build/docs/api/sillbeam-core.en.json'.\PHP_EOL;
} catch (Throwable $error) {
    fwrite(\STDERR, 'PHPDoc JSON export failed: '.$error->getMessage().\PHP_EOL);
    $exitCode = 1;
} finally {
    if (is_string($pending) && is_file($pending)) {
        unlink($pending);
    }
    if (is_dir($temporary)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($temporary);
    }
}

exit($exitCode);
