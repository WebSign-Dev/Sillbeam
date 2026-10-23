<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalyzer;
use Sillbeam\Core\Architecture\ArchitectureValidationException;
use Sillbeam\Core\Architecture\DiscoveryOptions;
use Sillbeam\Core\Tests\Support\SourceWorkspace;
use Sillbeam\Core\Tests\Support\TestApplicationKernel;

final class ArchitectureAnalysisTest extends TestCase
{
    private SourceWorkspace $source;

    protected function setUp(): void
    {
        $this->source = new SourceWorkspace();
    }

    protected function tearDown(): void
    {
        $this->source->remove();
    }

    public function testIndependentGraphAndCodeViolationsShareOneResultWithSourceLines(): void
    {
        $a = $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: [\\Missing\\Module::class])] final class AModule {}');
        $b = $this->source->php('src/Module/B/BModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class BModule {}');
        $port = $this->source->php('src/Module/B/Port.php', 'interface Port {}');
        $this->source->php('src/Module/A/Consumer.php', "final class Consumer {\n public \\".$port.' $one; public \\'.$port." \$two;\n public \\".$port." \$three;\n}");
        $analyzer = new ArchitectureAnalyzer();
        $result = $analyzer->analyze($this->options());
        self::assertTrue($result->isComplete());
        self::assertNull($result->architecture);
        self::assertSame(['code.undeclared_dependency', 'code.undeclared_dependency', 'dependency.unknown'], array_column($result->findings, 'rule'));
        self::assertSame([2, 3], array_map(static fn ($finding) => $finding->context['line'], \array_slice($result->findings, 0, 2)));
        self::assertSame($a, $result->findings[0]->context['module']);
        self::assertSame($b, $result->findings[0]->context['target']);
        self::assertCount(2, $result->references[$a]);
        self::assertEquals($result->findings, $analyzer->analyze($this->options())->findings);
    }

    public function testSyntaxErrorIsAFatalAnalysisFailureWithOriginalCause(): void
    {
        $this->source->write('src/Module/Broken/Broken.php', "<?php\nclass {");
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertFalse($result->isComplete());
        self::assertFalse($result->isValid());
        self::assertNull($result->architecture);
        self::assertCount(1, $result->findings);
        self::assertSame('source.parse', $result->findings[0]->rule);
        self::assertSame('fatal', $result->findings[0]->severity);
        self::assertSame(2, $result->findings[0]->context['line']);
        try {
            $result->throwIfInvalid();
            self::fail('A fatal analysis must reject runtime use.');
        } catch (ArchitectureValidationException $error) {
            self::assertInstanceOf(\PhpParser\Error::class, $error->getPrevious());
        }
    }

    public function testOverlappingAreasAreFatalAndNeverProduceAPartialRuntimeGraph(): void
    {
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class AModule {}');
        $this->source->php('src/Module/A/Module/B/BModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class BModule {}');
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertFalse($result->isComplete());
        self::assertNull($result->architecture);
        self::assertSame('discovery.module_overlap', $result->findings[0]->rule);
    }

    #[DataProvider('doctrineModes')]
    public function testKernelRejectsAllCollectedViolationsBeforeRegisteringServices(bool $doctrine): void
    {
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: [\\Missing\\One::class])] final class AModule {}');
        $this->source->php('src/Module/B/BModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: [\\Missing\\Two::class])] final class BModule {}');
        $this->source->write('config/packages/framework.yaml', "framework: {secret: test, test: true}\n");
        $this->source->write('config/services.yaml', 'services: {}');
        $kernel = new TestApplicationKernel($this->source, $doctrine);
        try {
            $kernel->boot();
            self::fail('Container boot must reject the invalid graph.');
        } catch (ArchitectureValidationException $error) {
            self::assertCount(2, $error->result->findings);
            self::assertTrue($error->result->isComplete());
            self::assertNull($error->result->architecture);
            self::assertStringContainsString('Missing\\One', $error->getMessage());
            self::assertStringContainsString('Missing\\Two', $error->getMessage());
        } finally {
            $kernel->shutdown();
        }
    }

    public function testValidResultIsReusableAndDoesNotRetainPreviousFindings(): void
    {
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class AModule {}');
        $analyzer = new ArchitectureAnalyzer();
        $invalid = $analyzer->analyze($this->options(), ['modules' => ['missing' => ['enabled' => false]]]);
        self::assertFalse($invalid->isValid());
        $valid = $analyzer->analyze($this->options());
        self::assertTrue($valid->isValid());
        self::assertTrue($valid->isComplete());
        self::assertSame([], $valid->findings);
        self::assertNotNull($valid->architecture);
        $valid->throwIfInvalid();
    }

    public function testDisabledDependenciesRemainInactiveForCodeAnalysis(): void
    {
        $b = $this->source->php('src/Module/B/BModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class BModule {}');
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: [\\'.$b.'::class])] final class AModule {}');
        $port = $this->source->php('src/Module/B/Port.php', 'interface Port {}');
        $this->source->php('src/Module/A/Consumer.php', 'final class Consumer { public \\'.$port.' $port; }');
        $result = (new ArchitectureAnalyzer())->analyze($this->options(), ['modules' => ['b' => ['enabled' => false]]]);
        self::assertSame(['code.inactive', 'dependency.disabled'], array_column($result->findings, 'rule'));
        self::assertNull($result->architecture);
    }

    public function testDuplicateSourceSymbolsRetainEarlierViolationsAndStopCodeAnalysis(): void
    {
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: [\\Missing\\Module::class])] final class AModule {}');
        $this->source->php('src/Module/A/One.php', 'class Duplicate {}');
        $this->source->php('src/Module/A/Two.php', 'class Duplicate {}');
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertFalse($result->isComplete());
        self::assertSame(['dependency.unknown', 'source.duplicate_class'], array_column($result->findings, 'rule'));
        self::assertSame([], $result->references);
        self::assertNull($result->architecture);
    }

    public function testInvalidDescriptorMetadataIsFatalRatherThanAnEmptyArchitecture(): void
    {
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: "invalid")] final class AModule {}');
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertFalse($result->isComplete());
        self::assertNull($result->architecture);
        self::assertSame('discovery.descriptor_metadata', $result->findings[0]->rule);
        self::assertSame($this->source->directory.'/src/Module/A/AModule.php', $result->findings[0]->context['file']);
    }

    #[DataProvider('doctrineModes')]
    public function testKernelPreservesFatalSourceFailure(bool $doctrine): void
    {
        $this->source->write('src/Module/Broken/Broken.php', '<?php class {');
        $this->source->write('config/packages/framework.yaml', "framework: {secret: test, test: true}\n");
        $this->source->write('config/services.yaml', 'services: {}');
        $kernel = new TestApplicationKernel($this->source, $doctrine);
        try {
            $kernel->boot();
            self::fail('Container boot must fail on incomplete analysis.');
        } catch (ArchitectureValidationException $error) {
            self::assertFalse($error->result->isComplete());
            self::assertSame('source.parse', $error->result->findings[0]->rule);
        } finally {
            $kernel->shutdown();
        }
    }

    public function testFatalSourceSelectionIsIndependentOfDiscoveryRootOrder(): void
    {
        $this->source->write('z/Bad.php', '<?php class {');
        $this->source->write('a/Bad.php', '<?php class {');
        $roots = [
            new \Sillbeam\Core\Architecture\DiscoveryRoot($this->source->directory.'/z', 'Z\\'),
            new \Sillbeam\Core\Architecture\DiscoveryRoot($this->source->directory.'/a', 'A\\'),
        ];
        $options = new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\', $roots, []);
        $result = (new ArchitectureAnalyzer())->analyze($options);
        self::assertSame($this->source->directory.'/a/Bad.php', $result->findings[0]->context['file']);
        self::assertCount(2, $result->findings);
        $reversed = new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\', array_reverse($roots), []);
        self::assertEquals($result->findings, (new ArchitectureAnalyzer())->analyze($reversed)->findings);
    }

    public function testUnexpectedDescriptorExceptionsAreNotSwallowedAsRuleViolations(): void
    {
        $this->source->php('src/Module/A/AModule.php', 'throw new \\RuntimeException("descriptor execution failed"); #[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class AModule {}');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('descriptor execution failed');
        (new ArchitectureAnalyzer())->analyze($this->options());
    }

    public function testReferenceDeduplicationUsesPhpCaseInsensitiveSymbolIdentity(): void
    {
        $this->source->php('src/Module/A/AModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class AModule {}');
        $this->source->php('src/Module/B/BModule.php', '#[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class BModule {}');
        $port = $this->source->php('src/Module/B/Port.php', 'interface Port {}');
        $this->source->php('src/Module/A/Consumer.php', 'final class Consumer { public \\'.$port.' $one; public \\'.strtolower($port).' $two; }');
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertCount(1, $result->findings);
        self::assertSame('code.undeclared_dependency', $result->findings[0]->rule);
    }

    public function testDescriptorExecutionErrorsPropagateWithoutBecomingMetadataFindings(): void
    {
        $this->source->php('src/Module/A/AModule.php', 'sillbeamMissingDescriptorFunction(); #[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class AModule {}');
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('sillbeamMissingDescriptorFunction');
        (new ArchitectureAnalyzer())->analyze($this->options());
    }

    #[DataProvider('invalidIdentities')]
    public function testInvalidExplicitAndDerivedDescriptorIdsBlockReliableAnalysis(string $area, string $name, string $argument, string $rule): void
    {
        $attribute = 'Sillbeam\\Core\\'.$area.'\\Attribute\\Sillbeam'.$area;
        $class = $this->source->php('src/'.$area.'/Invalid/'.$name.'.php', '#[\\'.$attribute.'('.$argument.')] final class '.$name.' {}');
        $result = (new ArchitectureAnalyzer())->analyze($this->options());
        self::assertFalse($result->isComplete());
        self::assertNull($result->architecture);
        self::assertSame($rule, $result->findings[0]->rule);
        self::assertSame($class, $result->findings[0]->context['class']);
    }

    /** @return iterable<string, array{string, string, string, string}> */
    public static function invalidIdentities(): iterable
    {
        yield 'module explicit' => ['Module', 'AModule', 'id: "Bad_ID"', 'discovery.descriptor_metadata'];
        yield 'project explicit' => ['Project', 'AProject', 'id: "Bad_ID"', 'discovery.descriptor_metadata'];
        yield 'module derived' => ['Module', 'Module', '', 'discovery.descriptor_id'];
        yield 'project derived' => ['Project', 'Project', '', 'discovery.descriptor_id'];
    }

    /** @return iterable<string, array{bool}> */
    public static function doctrineModes(): iterable
    {
        yield 'without Doctrine' => [false];
        yield 'with Doctrine' => [true];
    }

    private function options(): DiscoveryOptions
    {
        return new DiscoveryOptions($this->source->directory.'/src', $this->source->namespace.'\\');
    }
}
