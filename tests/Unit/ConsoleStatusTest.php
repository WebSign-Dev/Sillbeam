<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\MakeModuleCommand;
use Sillbeam\Core\Architecture\Console\MakeProjectCommand;
use Sillbeam\Core\Architecture\Console\ReportCommand;
use Sillbeam\Core\Architecture\DescriptorCreator;
use Sillbeam\Core\Architecture\DiscoveryOptions;
use Sillbeam\Core\Kernel\Command\AboutCommand;
use Sillbeam\Core\Kernel\Runtime\KernelInformationInterface;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Symfony\Component\Console\Tester\CommandTester;

final class ConsoleStatusTest extends TestCase
{
    public function testGeneratorsAndReportUseCompactStatusesWithUnchangedEffects(): void
    {
        $source = new SourceWorkspace();
        try {
            $creator = new DescriptorCreator(new DiscoveryOptions($source->directory.'/src', $source->namespace.'\\'));
            foreach ([new MakeModuleCommand($creator), new MakeProjectCommand($creator)] as $command) {
                $tester = new CommandTester($command);
                self::assertSame(0, $tester->execute(['Name' => 'user']));
                self::assertStringContainsString('✓ Created', $tester->getDisplay());
                self::assertSame(1, $tester->execute(['Name' => 'user']));
                self::assertStringContainsString('✕ Creation target already exists', $tester->getDisplay());
                self::assertStringNotContainsString('[ERROR]', $tester->getDisplay());
            }
            $tester = new CommandTester(new ReportCommand(ArchitectureReport::fromAnalysis(new ArchitectureAnalysisResult()), $source->directory));
            self::assertSame(0, $tester->execute([]));
            self::assertStringContainsString('✓ Architecture report written', $tester->getDisplay());
            self::assertFileExists($source->directory.'/architecture-report.md');
            self::assertSame(2, $tester->execute(['--output' => 'missing/report.md']));
            self::assertStringContainsString('✕ Cannot write architecture report', $tester->getDisplay());
        } finally {
            $source->remove();
        }
    }

    public function testAboutKeepsAllInformationAndEscapesApplicationText(): void
    {
        $information = new class implements KernelInformationInterface {
            public function all(): array
            {
                return ['application' => '<error>Literal</error>'."\033[2J", 'symfony' => 'test', 'modules' => 3];
            }
        };
        $tester = new CommandTester(new AboutCommand($information));
        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('SILLBEAM CORE  / About', $tester->getDisplay());
        self::assertStringContainsString('Application: <error>Literal</error>', $tester->getDisplay());
        self::assertStringContainsString('Modules: 3', $tester->getDisplay());
        self::assertStringNotContainsString("\033", $tester->getDisplay());
    }
}
