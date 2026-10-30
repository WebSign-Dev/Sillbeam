<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Tests\Support\PackageArchive;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Symfony\Component\Filesystem\Filesystem;

final class PackageDistributionTest extends TestCase
{
    public function testActualComposerArchiveContainsOnlyRuntimeAndUsablePublicDocumentation(): void
    {
        $source = new SourceWorkspace();
        $repository = \dirname(__DIR__, 2);
        $internal = $repository.'/docs/superpowers/release-test-'.bin2hex(random_bytes(6)).'.md';
        $rootNoise = $repository.'/distribution-probe-'.bin2hex(random_bytes(6)).'.txt';
        $docNoise = $repository.'/docs/distribution-probe-'.bin2hex(random_bytes(6));
        $publicNoise = $repository.'/docs/reference/distribution-probe-'.bin2hex(random_bytes(6));
        $filesystem = new Filesystem();
        try {
            // Reproduce the previously leaked plan through the real Composer archiver.
            $filesystem->dumpFile($internal, 'Internal plan must never be shipped.');
            $filesystem->dumpFile($rootNoise, 'Temporary root helper must never be shipped.');
            $filesystem->dumpFile($docNoise.'/measurements.jsonl', '{}');
            foreach (['output.log', 'measurements.jsonl', 'reference.php', 'temporary.tmp', 'result.cache', 'backup.bak'] as $file) {
                $filesystem->dumpFile($publicNoise.'/'.$file, 'Development artifact');
            }
            $process = proc_open(['composer', 'archive', '--format=zip', '--dir='.$source->directory, '--file=package'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $repository);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $output);
            $archive = $source->directory.'/package.zip';
            $files = [];
            foreach (new \RecursiveIteratorIterator(new \PharData($archive)) as $file) {
                $relative = substr($file->getPathname(), \strlen('phar://'.$archive.'/'));
                $files[] = $relative;
                self::assertDoesNotMatchRegularExpression('~^(?:vendor|apps|tests|tools|skeleton|flex-recipe|docs/(?:superpowers|development|adr|packages))/|(?:^|/)(?:\.env(?:\..*)?|[^/]*\.cache|reference\.php)$~', $relative);
            }
            self::assertContains('src/SillbeamCoreBundle.php', $files);
            self::assertContains('LICENSE', $files);
            self::assertContains('SECURITY.md', $files);
            self::assertContains('docs/guides/getting-started.md', $files);
            self::assertNotContains('composer.lock', $files);
            self::assertNotContains(basename($rootNoise), $files);
            self::assertNotContains('docs/'.basename($docNoise).'/measurements.jsonl', $files);
            self::assertFalse(array_any($files, static fn (string $file): bool => str_starts_with($file, 'docs/reference/'.basename($publicNoise).'/')));
            $inventory = PackageArchive::inspect($archive, $repository);
            self::assertSame($files, $inventory['files']);
            self::assertGreaterThan(0, $inventory['markdown_links']);
            self::assertGreaterThan(0, $inventory['source_files']);
            self::assertContains('composer.json', $files);
            self::assertContains('docs/reference/doctor-report.schema.json', $files);
            self::assertContains('docs/reference/phpdoc-api.schema.json', $files);
            self::assertContains('docs/assets/sillbeam-header.png', $files);

            foreach ([
                ['README.md', '[excluded tests](tests/Unit/PackageDistributionTest.php)', 'Broken package link'],
                ['docs/reference/distribution-probe.md', '[excluded evidence][evidence]'."\n".'[evidence]: ../development/release-0.3.0-acceptance/README.md', 'Broken package link'],
                ['docs/reference/distribution-image.md', '<img src="../../development/hidden.png">', 'Broken package link'],
                ['docs/development/distribution-probe.log', 'development log', 'Unexpected package entry'],
                ['src/SillbeamCoreBundle.php', '<?php // corrupted source', 'Runtime source differs'],
            ] as $index => [$path, $contents, $message]) {
                $copy = $source->directory.'/invalid-'.$index.'.zip';
                copy($archive, $copy);
                $modified = new \PharData($copy);
                $modified->addFromString($path, $contents);
                unset($modified);
                try {
                    PackageArchive::inspect($copy, $repository);
                    self::fail('Archive corruption was not detected: '.$path);
                } catch (\RuntimeException $error) {
                    self::assertStringContainsString($message, $error->getMessage());
                }
            }
        } finally {
            $filesystem->remove([$internal, $rootNoise, $docNoise, $publicNoise]);
            @rmdir(\dirname($internal));
            $source->remove();
        }
    }
}
