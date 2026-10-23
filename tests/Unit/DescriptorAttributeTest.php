<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Module\Attribute\SillbeamModule;
use Sillbeam\Core\Project\Attribute\SillbeamProject;

final class DescriptorAttributeTest extends TestCase
{
    /** @return iterable<string, array{class-string<SillbeamModule|SillbeamProject>}> */
    public static function attributes(): iterable
    {
        yield 'module' => [SillbeamModule::class];
        yield 'project' => [SillbeamProject::class];
    }

    /** @param class-string<SillbeamModule|SillbeamProject> $attribute */
    #[DataProvider('attributes')]
    public function testIdCannotContainATrailingNewline(string $attribute): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new $attribute(id: "valid\n");
    }
}
