<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureAnalysisResult;
use Sillbeam\Core\Architecture\ArchitectureCompiler;
use Sillbeam\Core\Architecture\ArchitectureFinding;
use Sillbeam\Core\Architecture\ArchitectureFindings;
use Sillbeam\Core\Architecture\Diagnostic\DeclaredDependencyGraph;
use Sillbeam\Core\Architecture\Diagnostic\DiagnosticContext;
use Sillbeam\Core\Architecture\Diagnostic\Resolution;
use Sillbeam\Core\Architecture\DiscoveryResult;
use Sillbeam\Core\Module\ModuleDefinition;
use Sillbeam\Core\Project\ProjectDefinition;

final class DiagnosticGraphTest extends TestCase
{
    public function testDirectedPathsAreShortestDeterministicAndUseDeclarationsOnly(): void
    {
        $a = $this->module(DiagnosticA::class, [DiagnosticC::class, DiagnosticB::class]);
        $b = $this->module(DiagnosticB::class, [DiagnosticD::class]);
        $c = $this->module(DiagnosticC::class, [DiagnosticD::class]);
        $d = $this->module(DiagnosticD::class);
        $graph = new DeclaredDependencyGraph($this->discovery([$d, $c, $b, $a])->modules);
        self::assertSame([DiagnosticA::class, DiagnosticB::class, DiagnosticD::class], $graph->path(DiagnosticA::class, DiagnosticD::class));
        self::assertNull($graph->path(DiagnosticD::class, DiagnosticA::class));
        self::assertNull($graph->path(DiagnosticA::class, DiagnosticMissing::class));
        self::assertSame([DiagnosticA::class], $graph->path(DiagnosticA::class, DiagnosticA::class));
        self::assertSame([$a->class => $a->descriptorFile, $b->class => $b->descriptorFile], $graph->sourceDescriptors([DiagnosticA::class, DiagnosticB::class, DiagnosticD::class]));
        $this->expectException(\InvalidArgumentException::class);
        $graph->sourceDescriptors([DiagnosticD::class, DiagnosticA::class]);
    }

    public function testCyclesKeepDirectedOrderRatherThanSortingParticipants(): void
    {
        $modules = [$this->module(DiagnosticA::class, [DiagnosticC::class]), $this->module(DiagnosticB::class, [DiagnosticA::class]), $this->module(DiagnosticC::class, [DiagnosticB::class]), $this->module(DiagnosticD::class, [DiagnosticD::class])];
        $graph = new DeclaredDependencyGraph($this->discovery($modules)->modules);
        self::assertSame([[DiagnosticA::class, DiagnosticC::class, DiagnosticB::class, DiagnosticA::class], [DiagnosticD::class, DiagnosticD::class]], $graph->cycles());
        self::assertSame($graph->cycles(), (new DeclaredDependencyGraph($this->discovery(array_reverse($modules))->modules))->cycles());
    }

    public function testTheoreticalDependencyFindsExistingReturnPathAndSourceDescriptor(): void
    {
        $context = $this->context([$this->module(DiagnosticA::class), $this->module(DiagnosticB::class, [DiagnosticC::class]), $this->module(DiagnosticC::class, [DiagnosticA::class])]);
        $assessment = $context->assessDependency(DiagnosticA::class, DiagnosticB::class);
        self::assertSame([DiagnosticB::class, DiagnosticC::class, DiagnosticA::class], $assessment->returnPath);
        self::assertTrue($assessment->hasReturnPath);
        self::assertSame('/src/DiagnosticA/Descriptor.php', $assessment->sourceDescriptor);
        self::assertContains('cycle', $assessment->blockers);
        self::assertNull($assessment->resolution());
    }

    public function testMissingReturnPathIsNotItselfPermissionToChangeArchitecture(): void
    {
        $context = $this->context([$this->module(DiagnosticA::class), $this->module(DiagnosticB::class)]);
        $assessment = $context->assessDependency(DiagnosticA::class, DiagnosticB::class);
        self::assertFalse($assessment->hasReturnPath);
        self::assertNull($assessment->returnPath);
        self::assertTrue($assessment->privacyAllowed);
        self::assertFalse($assessment->alreadyDeclared);
        self::assertSame([], $assessment->blockers);
        $resolution = $assessment->resolution();
        self::assertInstanceOf(Resolution::class, $resolution);
        self::assertSame('option', $resolution->kind);
        self::assertSame(['/src/DiagnosticA/Descriptor.php'], $resolution->files);
        self::assertStringContainsString(DiagnosticB::class, implode(' ', $resolution->steps));
    }

    public function testPrivateModulesStayInsideTheirOwningProject(): void
    {
        $modules = [$this->module(DiagnosticA::class), $this->module(DiagnosticB::class, project: DiagnosticShop::class), $this->module(DiagnosticC::class, project: DiagnosticShop::class), $this->module(DiagnosticD::class, project: DiagnosticOther::class)];
        $context = $this->context($modules, [$this->project(DiagnosticShop::class), $this->project(DiagnosticOther::class)]);
        self::assertFalse($context->assessDependency(DiagnosticA::class, DiagnosticB::class)->privacyAllowed);
        self::assertFalse($context->assessDependency(DiagnosticB::class, DiagnosticD::class)->privacyAllowed);
        self::assertTrue($context->assessDependency(DiagnosticB::class, DiagnosticC::class)->privacyAllowed);
        self::assertTrue($context->assessDependency(DiagnosticB::class, DiagnosticA::class)->privacyAllowed);
        self::assertContains('private_boundary', $context->assessDependency(DiagnosticA::class, DiagnosticB::class)->blockers);
    }

    public function testStatesDistinguishDisabledUnassignedAndProjectSpecificActivation(): void
    {
        $context = $this->context(
            [$this->module(DiagnosticA::class), $this->module(DiagnosticB::class), $this->module(DiagnosticC::class), $this->module(DiagnosticD::class)],
            [$this->project(DiagnosticShop::class, [DiagnosticA::class]), $this->project(DiagnosticOther::class, [DiagnosticD::class])],
            ['modules' => ['DiagnosticB' => ['enabled' => false]]],
        );
        self::assertSame('active', $context->state(DiagnosticA::class));
        self::assertSame('disabled', $context->state(DiagnosticB::class));
        self::assertSame('unassigned', $context->state(DiagnosticC::class));
        self::assertSame('unknown', $context->state(DiagnosticMissing::class));
        self::assertSame('unassigned', $context->state(DiagnosticD::class, DiagnosticShop::class));
        self::assertSame('active', $context->state(DiagnosticD::class, DiagnosticOther::class));
        self::assertContains('target_disabled', $context->assessDependency(DiagnosticA::class, DiagnosticB::class)->blockers);
        self::assertSame('unassigned', $context->assessDependency(DiagnosticA::class, DiagnosticC::class)->targetState);
        self::assertSame([DiagnosticShop::class => 'unassigned'], $context->assessDependency(DiagnosticA::class, DiagnosticD::class)->targetProjectStates);
    }

    public function testFatalEvidenceCannotProveAbsenceOrSafeEditsEvenAfterCompilation(): void
    {
        $context = $this->context([$this->module(DiagnosticA::class), $this->module(DiagnosticB::class)], fatal: true);
        self::assertFalse($context->complete);
        self::assertSame('unknown', $context->state(DiagnosticA::class));
        $assessment = $context->assessDependency(DiagnosticA::class, DiagnosticB::class);
        self::assertNull($assessment->hasReturnPath);
        self::assertNull($assessment->alreadyDeclared);
        self::assertNull($assessment->privacyAllowed);
        self::assertContains('incomplete', $assessment->blockers);
        self::assertNull($assessment->resolution());
        $empty = DiagnosticContext::fromAnalysis(new ArchitectureAnalysisResult([new ArchitectureFinding('source.parse', 'Broken', severity: 'fatal')]));
        self::assertSame([], $empty->modules);
        self::assertSame('unknown', $empty->state(DiagnosticA::class));
        self::assertNull($empty->assessDependency(DiagnosticA::class, DiagnosticB::class)->hasReturnPath);
    }

    public function testIncompleteContextRetainsPositivePathEvidenceAndRejectsUnknownTargets(): void
    {
        $context = $this->context([$this->module(DiagnosticA::class), $this->module(DiagnosticB::class, [DiagnosticA::class])], fatal: true);
        self::assertTrue($context->assessDependency(DiagnosticA::class, DiagnosticB::class)->hasReturnPath);
        $missing = $context->assessDependency(DiagnosticA::class, DiagnosticMissing::class);
        self::assertNull($missing->resolution());
        self::assertContains('unknown_target', $missing->blockers);
    }

    public function testExistingDeclarationsDoNotProduceAddDependencyOptions(): void
    {
        $context = $this->context([$this->module(DiagnosticA::class, [DiagnosticB::class]), $this->module(DiagnosticB::class)]);
        self::assertTrue($context->assessDependency(DiagnosticA::class, DiagnosticB::class)->alreadyDeclared);
        self::assertNull($context->assessDependency(DiagnosticA::class, DiagnosticB::class)->resolution());
    }

    public function testUnknownReachableDeclarationsDoNotProveAbsenceOfAReturnPath(): void
    {
        $context = $this->context([$this->module(DiagnosticA::class), $this->module(DiagnosticB::class, [DiagnosticMissing::class])]);
        $assessment = $context->assessDependency(DiagnosticA::class, DiagnosticB::class);
        self::assertNull($assessment->hasReturnPath);
        self::assertContains('unresolved_dependencies', $assessment->blockers);
        self::assertNull($assessment->resolution());
    }

    public function testObservedReferencesNeverBecomeDeclaredGraphEdges(): void
    {
        $discovery = $this->discovery([$this->module(DiagnosticA::class), $this->module(DiagnosticB::class)]);
        $candidate = (new ArchitectureCompiler())->compile($discovery, []);
        $references = [DiagnosticA::class => [['module' => DiagnosticB::class, 'class' => 'Port', 'file' => '/src/Consumer.php', 'line' => 7]]];
        $context = DiagnosticContext::fromEvidence($discovery, $candidate, true, $references);
        self::assertNotNull($context->relationship(DiagnosticA::class, DiagnosticB::class));
        self::assertNull($context->graph()->path(DiagnosticA::class, DiagnosticB::class));
        self::assertFalse($context->assessDependency(DiagnosticA::class, DiagnosticB::class)->alreadyDeclared);
        self::assertSame($references, $context->references);
        self::assertSame([], $discovery->modules[DiagnosticA::class]->dependencies);
    }

    /** @param list<ModuleDefinition> $modules
     * @param list<ProjectDefinition> $projects
     * @param array<string, mixed>    $config
     */
    private function context(array $modules, array $projects = [], array $config = [], bool $fatal = false): DiagnosticContext
    {
        $discovery = $this->discovery($modules, $projects);
        $findings = new ArchitectureFindings();
        $candidate = (new ArchitectureCompiler())->collect($discovery, $config, $findings);

        return DiagnosticContext::fromEvidence($discovery, $candidate, !$fatal && !$findings->hasFatal());
    }

    /** @param list<ModuleDefinition> $modules
     * @param list<ProjectDefinition> $projects
     */
    private function discovery(array $modules, array $projects = []): DiscoveryResult
    {
        return new DiscoveryResult('/src', 'Fixture\\', array_column($modules, null, 'class'), array_column($projects, null, 'class'), []);
    }

    /** @param class-string $class
     * @param list<class-string> $dependencies
     * @param class-string|null  $project
     */
    private function module(string $class, array $dependencies = [], ?string $project = null): ModuleDefinition
    {
        $id = substr($class, strrpos($class, '\\') + 1);

        return new ModuleDefinition($id, $class, '', $dependencies, true, '/src/'.$id, '/src/'.$id.'/Descriptor.php', 'Fixture', [], [], $project);
    }

    /** @param class-string $class
     * @param list<class-string> $modules
     */
    private function project(string $class, array $modules = []): ProjectDefinition
    {
        $id = substr($class, strrpos($class, '\\') + 1);

        return new ProjectDefinition($id, $class, '', $modules, [], '/src/'.$id, '/src/'.$id.'/Project.php', 'Fixture', [$id.'.test'], []);
    }
}

final class DiagnosticA
{
}
final class DiagnosticB
{
}
final class DiagnosticC
{
}
final class DiagnosticD
{
}
final class DiagnosticMissing
{
}
final class DiagnosticShop
{
}
final class DiagnosticOther
{
}
