<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Tests\Support\SourceWorkspace;
use Symfony\Component\Filesystem\Filesystem;

final class PackageDistributionTest extends TestCase
{
    public function testActualComposerArchiveExcludesInternalPlansAndDevelopmentFiles(): void
    {
        $source = new SourceWorkspace();
        $repository = \dirname(__DIR__, 2);
        $internal = $repository.'/docs/superpowers/release-test-'.bin2hex(random_bytes(6)).'.md';
        $filesystem = new Filesystem();
        try {
            // Reproduce the previously leaked plan through the real Composer archiver.
            $filesystem->dumpFile($internal, 'Internal plan must never be shipped.');
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
                self::assertDoesNotMatchRegularExpression('~^(?:vendor|apps|tests|tools|skeleton|flex-recipe|docs/superpowers)/|(?:^|/)(?:\.env(?:\..*)?|[^/]*\.cache|reference\.php)$~', $relative);
            }
            self::assertContains('src/SillbeamBundle.php', $files);
            self::assertContains('LICENSE', $files);
            self::assertContains('SECURITY.md', $files);
            self::assertContains('docs/guides/getting-started.md', $files);
            self::assertNotContains('composer.lock', $files);
        } finally {
            $filesystem->remove($internal);
            @rmdir(\dirname($internal));
            $source->remove();
        }
    }
}
