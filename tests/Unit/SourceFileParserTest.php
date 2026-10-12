<?php

declare(strict_types=1);

namespace Sillbeam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Architecture\SourceFileParser;
use Sillbeam\Tests\Support\SourceWorkspace;

final class SourceFileParserTest extends TestCase
{
    public function testStaticReferencesResolveGroupedImportsAndAllSupportedTypePositions(): void
    {
        $source = new SourceWorkspace();
        try {
            $source->php('src/Example.php', <<<'CODE'
use External\{Base, Contract as Port, Mix, Marker, Failure, Value, Other, Third};
#[Marker(Value::class)]
final class Example extends Base implements Port {
    use Mix;
    public Value|Other $union;
    public Port&Third $intersection;
    public function run(?Value $value): Other|Third {
        try {
            $other = new Other();
            Other::run();
            Other::$state;
            return $other instanceof Third ? $other : throw new Failure();
        } catch (Failure $error) { throw $error; }
    }
}
CODE);
            $file = (new SourceFileParser())->parse($source->directory.'/src/Example.php');
            $classes = array_values(array_unique(array_column($file->references, 'class')));
            sort($classes);
            self::assertSame(['External\Base', 'External\Contract', 'External\Failure', 'External\Marker', 'External\Mix', 'External\Other', 'External\Third', 'External\Value'], $classes);
            self::assertSame([$source->namespace.'\Example'], $file->classes);
            self::assertGreaterThan(1, $file->references[0]['line']);
        } finally {
            $source->remove();
        }
    }

    public function testDynamicNamesStringsPhpDocAndUnusedImportsAreOutsideStaticAnalysis(): void
    {
        $source = new SourceWorkspace();
        try {
            $source->php('src/Example.php', <<<'CODE'
use External\Unused;
/** @property \External\Documented $value */
final class Example {
    public function run(string $class): object {
        $name = 'External\\StringTarget';
        $class::run();
        $class::$property;
        $class::CONSTANT;
        return new $class();
    }
}
CODE);
            $file = (new SourceFileParser())->parse($source->directory.'/src/Example.php');
            self::assertSame([], $file->references);
            self::assertSame([], $file->descriptors);
        } finally {
            $source->remove();
        }
    }
}
