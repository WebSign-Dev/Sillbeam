<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Architecture\ArchitectureCompiler;
use Sillbeam\Core\Architecture\ArchitectureValidationException;
use Sillbeam\Core\Architecture\DiscoveryResult;
use Sillbeam\Core\Module\ModuleDefinition;
use Sillbeam\Core\Project\ProjectDefinition;

final class ArchitectureFindingsTest extends TestCase
{
    public function testSingleViolationHasStructuredContextAndStillRejectsCompilation(): void
    {
        $module = $this->module(FindingAModule::class, [FindingMissingModule::class]);
        $result = $this->failure([$module]);
        self::assertTrue($result->isComplete());
        self::assertFalse($result->isValid());
        self::assertNull($result->architecture);
        self::assertCount(1, $result->findings);
        $finding = $result->findings[0];
        self::assertSame('dependency.unknown', $finding->rule);
        self::assertSame('error', $finding->severity);
        self::assertStringContainsString('depends on unknown module class', $finding->message);
        self::assertSame($module->class, $finding->context['module']);
        self::assertSame($module->descriptorFile, $finding->context['file']);
        self::assertSame(FindingMissingModule::class, $finding->context['target']);
    }

    public function testIndependentUnknownDependenciesAndCyclesAreOrderedAndDeduplicated(): void
    {
        $a = $this->module(FindingAModule::class, [FindingMissingModule::class, FindingMissingModule::class]);
        $b = $this->module(FindingBModule::class, [FindingCModule::class]);
        $c = $this->module(FindingCModule::class, [FindingBModule::class]);
        $d = $this->module(FindingDModule::class, [FindingMissingModule::class]);
        $forward = $this->failure([$a, $b, $c, $d]);
        $backward = $this->failure([$d, $c, $b, $a]);
        self::assertCount(3, $forward->findings);
        self::assertEquals($forward->findings, $backward->findings);
        self::assertSame(['dependency.cycle', 'dependency.unknown', 'dependency.unknown'], array_column($forward->findings, 'rule'));
    }

    public function testAmbiguousIdentitiesBlockActivationButStillCollectIndependentDependencies(): void
    {
        $a = $this->module(FindingAModule::class, [FindingMissingModule::class], 'same');
        $b = $this->module(FindingBModule::class, [], 'same');
        $result = $this->failure([$a, $b]);
        self::assertFalse($result->isComplete());
        self::assertNull($result->architecture);
        self::assertSame(['dependency.unknown', 'module.duplicate_key'], array_column($result->findings, 'rule'));
        self::assertSame('fatal', $result->findings[1]->severity);
    }

    public function testProjectHostAndPrivateBoundaryViolationsAreCollectedTogether(): void
    {
        $shop = new ProjectDefinition('shop', FindingShopProject::class, '', [FindingBModule::class], [], '/shop', '/shop/Project.php', 'Shop', [], []);
        $other = new ProjectDefinition('other', FindingOtherProject::class, '', [], [], '/other', '/other/Project.php', 'Other', [], []);
        $a = $this->module(FindingAModule::class, [FindingBModule::class]);
        $b = $this->module(FindingBModule::class, [], project: FindingOtherProject::class);
        $result = $this->failure([$a, $b], [$shop, $other], ['projects' => ['shop' => ['hosts' => ['https://bad.test']]]]);
        self::assertTrue($result->isComplete());
        self::assertSame(['dependency.private', 'project.host_invalid', 'project.host_required', 'project.private_module'], array_column($result->findings, 'rule'));
    }

    public function testUnreadableSourceCannotMasqueradeAsAnEmptyFile(): void
    {
        try {
            (new \Sillbeam\Core\Architecture\SourceFileParser())->parse('/sillbeam-nonexistent-source.php');
            self::fail('Unreadable source must stop reliable analysis.');
        } catch (ArchitectureValidationException $error) {
            self::assertFalse($error->result->isComplete());
            self::assertSame('source.read', $error->result->findings[0]->rule);
            self::assertSame('/sillbeam-nonexistent-source.php', $error->result->findings[0]->context['file']);
        }
    }

    public function testIndependentCyclesAndUnknownProjectReferencesAreAllReported(): void
    {
        $modules = [
            $this->module(FindingAModule::class, [FindingAModule::class]),
            $this->module(FindingBModule::class, [FindingCModule::class]),
            $this->module(FindingCModule::class, [FindingBModule::class]),
        ];
        $project = new ProjectDefinition('shop', FindingShopProject::class, '', [FindingMissingModule::class, FindingMissingModule::class], [], '/shop', '/shop/Project.php', 'Shop', [], []);
        $result = $this->failure($modules, [$project]);
        self::assertTrue($result->isComplete());
        self::assertSame(['dependency.cycle', 'dependency.cycle', 'project.unknown_module'], array_column($result->findings, 'rule'));
        self::assertSame('/shop/Project.php', $result->findings[2]->context['file']);
    }

    /** @param list<ModuleDefinition> $modules
     * @param list<ProjectDefinition> $projects
     * @param array<string, mixed>    $config
     */
    private function failure(array $modules, array $projects = [], array $config = []): \Sillbeam\Core\Architecture\ArchitectureAnalysisResult
    {
        $discovery = new DiscoveryResult('/src', 'Fixture\\', array_column($modules, null, 'class'), array_column($projects, null, 'class'), []);
        try {
            (new ArchitectureCompiler())->compile($discovery, $config);
            self::fail('Invalid architecture must never compile.');
        } catch (ArchitectureValidationException $error) {
            return $error->result;
        }
    }

    /** @param class-string $class
     * @param list<class-string> $dependencies
     * @param class-string|null  $project
     */
    private function module(string $class, array $dependencies = [], ?string $id = null, ?string $project = null): ModuleDefinition
    {
        $id ??= substr($class, strrpos($class, '\\') + 1);

        return new ModuleDefinition($id, $class, '', $dependencies, true, '/src/'.$id, '/src/'.$id.'/Module.php', 'Fixture', [], [], $project);
    }
}

final class FindingAModule
{
}
final class FindingBModule
{
}
final class FindingCModule
{
}
final class FindingDModule
{
}
final class FindingMissingModule
{
}
final class FindingShopProject
{
}
final class FindingOtherProject
{
}
