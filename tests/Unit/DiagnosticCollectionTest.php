<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureCompiler;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureFindings;
use Sillbeam\Core\Architecture\Diagnostic\DiagnosticCollection;
use Sillbeam\Core\Architecture\Diagnostic\DiagnosticContext;
use Sillbeam\Core\Architecture\Diagnostic\Resolution;
use Sillbeam\Core\Architecture\DiscoveryResult;
use Sillbeam\Core\Module\ModuleDefinition;

final class DiagnosticCollectionTest extends TestCase
{
    public function testAttributionPreservesEveryOriginalEntryIncludingDuplicates(): void
    {
        $context = $this->context();
        $one = new ArchitectureFinding('code.undeclared_dependency', 'First location', ['module' => CollectionA::class, 'target' => CollectionB::class, 'file' => '/src/A/Use.php', 'line' => 1]);
        $two = new ArchitectureFinding('code.inactive', 'Second location', ['module' => CollectionA::class, 'target' => CollectionB::class, 'line' => 2]);
        $other = new ArchitectureFinding('code.undeclared_dependency', 'Unrelated same rule', ['module' => CollectionC::class, 'target' => CollectionB::class]);
        $reverse = new ArchitectureFinding('dependency.custom', 'Reverse direction', ['module' => CollectionB::class, 'target' => CollectionA::class]);
        $unknown = new ArchitectureFinding('future.rule', 'No inferred cause', ['file' => '/config/app.yaml', 'detail' => 'preserve this'], 'fatal');
        $findings = [$one, $two, $other, $reverse, $unknown, $one];
        $collection = DiagnosticCollection::fromContext($findings, $context);
        self::assertSame($findings, $collection->findings);
        self::assertCount(4, $collection->diagnoses);
        self::assertCount(6, $collection->assignments);
        self::assertSame($collection->assignments[0], $collection->assignments[1]);
        self::assertSame($collection->assignments[0], $collection->assignments[5]);
        self::assertNotSame($collection->assignments[0], $collection->assignments[2]);
        self::assertNotSame($collection->assignments[0], $collection->assignments[3]);
        $coverage = [];
        foreach ($collection->diagnoses as $diagnosis) {
            foreach ($diagnosis->findingIndexes as $position => $index) {
                self::assertSame($findings[$index], $diagnosis->findings[$position]);
                self::assertSame($diagnosis->id, $collection->assignments[$index]);
                $coverage[] = $index;
            }
        }
        sort($coverage);
        self::assertSame([0, 1, 2, 3, 4, 5], $coverage);
    }

    public function testNoGroupingByRuleFileOrUnverifiedModulePair(): void
    {
        $findings = [];
        foreach ([1, 2] as $line) {
            $findings[] = new ArchitectureFinding('same.rule', 'Unproven', ['module' => CollectionC::class, 'target' => CollectionB::class, 'file' => '/same.php', 'line' => $line]);
        }
        $collection = DiagnosticCollection::fromContext($findings, $this->context());
        self::assertCount(2, $collection->diagnoses);
        self::assertSame(['finding', 'finding'], array_column($collection->diagnoses, 'basis'));
    }

    public function testOrderedCycleEvidenceDoesNotModifyExistingFindingOrRuntimeSafety(): void
    {
        $discovery = $this->discovery([
            $this->module(CollectionA::class, [CollectionC::class]),
            $this->module(CollectionB::class, [CollectionA::class]),
            $this->module(CollectionC::class, [CollectionB::class]),
        ]);
        $findings = new ArchitectureFindings();
        $candidate = (new ArchitectureCompiler())->collect($discovery, [], $findings);
        $analysis = new ArchitectureAnalysisResult($findings->all(), $candidate, discovery: $discovery);
        $original = serialize($analysis->findings);
        $collection = DiagnosticCollection::fromContext($analysis->findings, DiagnosticContext::fromEvidence($discovery, $candidate, true));
        self::assertNull($analysis->architecture);
        self::assertSame($original, serialize($analysis->findings));
        self::assertSame('declared_cycle', $collection->diagnoses[0]->basis);
        self::assertSame([CollectionA::class, CollectionC::class, CollectionB::class, CollectionA::class], $collection->diagnoses[0]->modules);
        self::assertCount(3, $collection->diagnoses[0]->descriptors);
        self::assertSame('option', $collection->diagnoses[0]->resolutions[0]->kind);
    }

    public function testGroupingAndRunLocalIdentificationAreDeterministic(): void
    {
        $findings = [new ArchitectureFinding('one.rule', 'One', ['module' => CollectionA::class, 'target' => CollectionB::class]), new ArchitectureFinding('two.rule', 'Two')];
        $first = DiagnosticCollection::fromContext($findings, $this->context());
        $second = DiagnosticCollection::fromContext($findings, $this->context(true));
        self::assertEquals($first, $second);
        self::assertSame(['D1', 'D2'], array_column($first->diagnoses, 'id'));
        $analysis = new ArchitectureAnalysisResult([new ArchitectureFinding('fatal.rule', 'No discovery', severity: 'fatal')]);
        $collection = DiagnosticCollection::fromAnalysis($analysis);
        self::assertCount(1, $collection->diagnoses);
        self::assertSame('technical', $collection->diagnoses[0]->resolutions[0]->kind);
        self::assertSame($analysis->findings, $collection->findings);
    }

    public function testFatalFindingsSuppressOtherwiseAvailableRelationshipOptions(): void
    {
        $finding = new ArchitectureFinding('code.undeclared_dependency', 'Direct access', ['module' => CollectionA::class, 'target' => CollectionC::class]);
        $collection = DiagnosticCollection::fromContext([$finding], $this->context());
        self::assertSame('observed_reference', $collection->diagnoses[0]->basis);
        self::assertSame('option', $collection->diagnoses[0]->resolutions[0]->kind);
        $fatal = DiagnosticCollection::fromContext([$finding, new ArchitectureFinding('source.read', 'Incomplete references', severity: 'fatal')], $this->context());
        foreach ($fatal->diagnoses as $diagnosis) {
            foreach ($diagnosis->resolutions as $resolution) {
                self::assertSame('technical', $resolution->kind);
                self::assertStringNotContainsString('::class', implode(' ', $resolution->steps));
            }
        }
    }

    public function testUnverifiedCycleMetadataCannotInventCycleEvidence(): void
    {
        $finding = new ArchitectureFinding('dependency.cycle', 'Alleged cycle', ['module' => CollectionA::class, 'cycle' => CollectionA::class.', '.CollectionC::class]);
        $collection = DiagnosticCollection::fromContext([$finding], $this->context());
        self::assertSame('finding', $collection->diagnoses[0]->basis);
        self::assertSame([], $collection->diagnoses[0]->modules);
        self::assertSame([CollectionA::class => '/src/'.CollectionA::class.'/Descriptor.php'], $collection->diagnoses[0]->descriptors);
        $empty = DiagnosticCollection::fromContext([], $this->context());
        self::assertSame([], $empty->findings);
        self::assertSame([], $empty->assignments);
        self::assertSame([], $empty->diagnoses);
    }

    public function testResolutionKindsDistinguishTechnicalStepsFromDomainOptions(): void
    {
        $technical = new Resolution('technical', ['Restore readable source file at the reported path.'], ['/source.php']);
        self::assertSame('technical', $technical->kind);
        $option = $this->context()->assessDependency(CollectionA::class, CollectionC::class)->resolution();
        self::assertInstanceOf(Resolution::class, $option);
        self::assertSame('option', $option->kind);
        $this->expectException(\InvalidArgumentException::class);
        new Resolution('technical', [], []);
    }

    private function context(bool $reverse = false): DiagnosticContext
    {
        $modules = [$this->module(CollectionA::class), $this->module(CollectionB::class, [CollectionA::class]), $this->module(CollectionC::class)];
        $discovery = $this->discovery($reverse ? array_reverse($modules) : $modules);
        $candidate = (new ArchitectureCompiler())->compile($discovery, []);

        return DiagnosticContext::fromEvidence($discovery, $candidate, true, [CollectionA::class => [
            ['module' => CollectionB::class, 'class' => 'SomePort', 'file' => '/src/A/Use.php', 'line' => 1],
            ['module' => CollectionC::class, 'class' => 'OtherPort', 'file' => '/src/A/Use.php', 'line' => 2],
        ]]);
    }

    /** @param list<ModuleDefinition> $modules */
    private function discovery(array $modules): DiscoveryResult
    {
        return new DiscoveryResult('/src', 'Fixture\\', array_column($modules, null, 'class'), [], []);
    }

    /** @param class-string $class
     * @param list<class-string> $dependencies
     */
    private function module(string $class, array $dependencies = []): ModuleDefinition
    {
        return new ModuleDefinition($class, $class, '', $dependencies, true, '/src/'.$class, '/src/'.$class.'/Descriptor.php', 'Fixture', [], []);
    }
}

final class CollectionA
{
}
final class CollectionB
{
}
final class CollectionC
{
}
