<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\Diagnostic\DiagnosticCollection;

final class DiagnosticRuleCatalogTest extends TestCase
{
    /** @return iterable<string, array{string, string, string}> */
    public static function rules(): iterable
    {
        yield 'dependency.unknown' => ['dependency.unknown', 'not discovered', 'descriptor'];
        yield 'dependency.private' => ['dependency.private', 'private', 'Declaring'];
        yield 'dependency.cycle' => ['dependency.cycle', 'cycle', 'depend'];
        yield 'dependency.disabled' => ['dependency.disabled', 'disabled', 'unknown'];
        yield 'code.undeclared_dependency' => ['code.undeclared_dependency', 'direct dependency', 'Check'];
        yield 'code.inactive' => ['code.inactive', 'inactive', 'unknown'];
        yield 'code.private' => ['code.private', 'private', 'Declaring'];
        yield 'code.unknown_class' => ['code.unknown_class', 'application class', 'autowiring'];
        yield 'project.unknown_module' => ['project.unknown_module', 'not discovered', 'descriptor'];
        yield 'project.private_module' => ['project.private_module', 'private', 'Declaring'];
        yield 'project.unknown_configuration' => ['project.unknown_configuration', 'configuration', 'ID'];
        yield 'project.host_invalid' => ['project.host_invalid', 'exact hostname', 'scheme'];
        yield 'project.host_required' => ['project.host_required', 'host', 'Project'];
        yield 'project.host_duplicate' => ['project.host_duplicate', 'host', 'Project'];
        yield 'project.duplicate_id' => ['project.duplicate_id', 'ID', 'unique'];
        yield 'module.duplicate_key' => ['module.duplicate_key', 'key', 'scope'];
        yield 'module.owner_missing' => ['module.owner_missing', 'owning Project', 'ownership'];
        yield 'module.unknown_override' => ['module.unknown_override', 'override', 'key'];
        yield 'discovery.owner_missing' => ['discovery.owner_missing', 'owning Project', 'ownership'];
        yield 'discovery.module_boundary' => ['discovery.module_boundary', 'module discovery', 'root'];
        yield 'discovery.project_boundary' => ['discovery.project_boundary', 'Project discovery', 'root'];
        yield 'discovery.module_overlap' => ['discovery.module_overlap', 'overlap', 'areas'];
        yield 'discovery.project_overlap' => ['discovery.project_overlap', 'overlap', 'areas'];
        yield 'discovery.project_inside_module' => ['discovery.project_inside_module', 'inside a module', 'areas'];
        yield 'discovery.descriptor_class' => ['discovery.descriptor_class', 'PSR-4', 'namespace'];
        yield 'discovery.descriptor_file' => ['discovery.descriptor_file', 'different file', 'class'];
        yield 'discovery.descriptor_metadata' => ['discovery.descriptor_metadata', 'attribute', 'arguments'];
        yield 'discovery.descriptor_id' => ['discovery.descriptor_id', 'derive', '[a-z][a-z0-9-]*'];
        yield 'discovery.multiple_descriptors' => ['discovery.multiple_descriptors', 'descriptor', 'one'];
        yield 'discovery.multiple_attributes' => ['discovery.multiple_attributes', 'attribute', 'one'];
        yield 'source.parse' => ['source.parse', 'syntax', 'reported'];
        yield 'source.read' => ['source.read', 'read', 'permissions'];
        yield 'source.duplicate_class' => ['source.duplicate_class', 'class', 'declaration'];
        yield 'report.execution' => ['report.execution', 'execution', 'rerun'];
    }

    #[DataProvider('rules')]
    public function testEveryRuleHasProblemImpactAndNextStepsWithoutInventingContext(string $rule, string $problem, string $advice): void
    {
        $finding = new ArchitectureFinding($rule, 'Original evidence.', severity: str_starts_with($rule, 'source.') || str_starts_with($rule, 'discovery.') || \in_array($rule, ['module.duplicate_key', 'module.owner_missing', 'project.duplicate_id', 'report.execution'], true) ? 'fatal' : 'error');
        $analysis = new ArchitectureAnalysisResult([$finding]);
        $collection = DiagnosticCollection::fromAnalysis($analysis);
        $detail = $collection->diagnoses[0]->details[0];
        self::assertSame($analysis->findings[0], $detail->finding);
        self::assertStringContainsString($problem, $detail->problem);
        self::assertNotSame('', $detail->impact);
        self::assertNotEmpty($detail->resolutions);
        $text = $detail->problem.' '.$detail->impact.' '.implode(' ', array_merge(...array_column($detail->resolutions, 'steps')));
        self::assertStringContainsString($advice, $text);
        self::assertStringNotContainsString('/src/', $text);
        foreach ($detail->resolutions as $resolution) {
            self::assertSame([], $resolution->files);
            self::assertSame([], $resolution->configuration);
        }
        self::assertSame(1, $collection->findingCount());
        self::assertSame(1, $collection->diagnosisCount());
        if ('fatal' === $finding->severity) {
            self::assertStringContainsString('Analysis incomplete', $detail->impact);
        }
    }

    public function testUnknownFutureRuleRetainsItsMessageAndUsesAnHonestFallback(): void
    {
        $analysis = new ArchitectureAnalysisResult([new ArchitectureFinding('future.rule', 'Custom original message.', ['file' => '/known.php', 'line' => 7])]);
        $detail = DiagnosticCollection::fromAnalysis($analysis)->diagnoses[0]->details[0];
        self::assertSame('Custom original message.', $detail->problem);
        self::assertStringContainsString('No rule-specific explanation', $detail->impact);
        self::assertSame('/known.php:7', $detail->location());
    }

    public function testFatalPrerequisitesPrecedeConsequencesWithoutChangingOriginalOrder(): void
    {
        $analysis = new ArchitectureAnalysisResult([
            new ArchitectureFinding('dependency.unknown', 'Missing module', ['module' => 'KnownModule', 'target' => 'MissingModule']),
            new ArchitectureFinding('source.read', 'Unreadable file', ['file' => '/blocked.php'], 'fatal'),
            new ArchitectureFinding('source.parse', 'Broken file', ['file' => '/syntax.php'], 'fatal'),
        ]);
        $collection = DiagnosticCollection::fromAnalysis($analysis);
        self::assertSame($analysis->findings, $collection->findings);
        self::assertSame('fatal', $collection->diagnoses[0]->findings[0]->severity);
        self::assertSame('fatal', $collection->diagnoses[1]->findings[0]->severity);
        self::assertSame('error', $collection->diagnoses[2]->findings[0]->severity);
        self::assertSame('dependency.unknown', $collection->findings[0]->rule);
        self::assertEquals($collection, DiagnosticCollection::fromAnalysis($analysis));
        self::assertCount(3, $collection->assignments);
    }
}
